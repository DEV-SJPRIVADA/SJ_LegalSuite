<?php

namespace App\Livewire\Licitaciones\DocumentosLegales;

use App\Models\Directory\DirectoryUser;
use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Documentos Legales')]
class DocumentosLegalesIndex extends Component
{
    /** Personas seleccionadas por carpeta (primera marcada como director). */
    /** @var array<int, list<array{email: string, name: string, is_primary: bool}>> */
    public array $assign = [];

    /** Búsqueda por carpeta: folderId => texto */
    /** @var array<int, string> */
    public array $directorBusqueda = [];

    public bool $showCreateFolder = false;

    public string $nuevaCarpetaNombre = '';

    public bool $nuevaCarpetaSinRecordatorios = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', LegalDocumentFolder::class);

        $this->hydrateAssignState();
    }

    private function hydrateAssignState(): void
    {
        LegalDocumentFolder::query()
            ->where('is_active', true)
            ->with('responsibles')
            ->get()
            ->each(function (LegalDocumentFolder $folder) {
                $people = $folder->responsibles->map(fn ($r) => [
                    'email' => strtolower((string) $r->email),
                    'name' => (string) ($r->name ?: $this->resolvePersonName((string) $r->email)),
                    'is_primary' => (bool) $r->is_primary,
                ])->values()->all();

                if ($people === []) {
                    $email = strtolower(trim((string) ($folder->responsible_email ?? '')));
                    if ($email === '' && $folder->responsible_user_id) {
                        $email = strtolower((string) (User::query()->whereKey($folder->responsible_user_id)->value('email') ?? ''));
                    }
                    if ($email !== '') {
                        $people[] = [
                            'email' => $email,
                            'name' => $this->resolvePersonName($email),
                            'is_primary' => true,
                        ];
                    }
                }

                if ($people !== [] && ! collect($people)->contains(fn ($p) => $p['is_primary'])) {
                    $people[0]['is_primary'] = true;
                }

                $this->assign[$folder->id] = $people;
                if (! array_key_exists($folder->id, $this->directorBusqueda)) {
                    $this->directorBusqueda[$folder->id] = '';
                }
            });
    }

    public function openCreateFolder(): void
    {
        Gate::authorize('create', LegalDocumentFolder::class);
        $this->showCreateFolder = true;
        $this->nuevaCarpetaNombre = '';
        $this->nuevaCarpetaSinRecordatorios = false;
        $this->resetErrorBag(['nuevaCarpetaNombre']);
    }

    public function cancelCreateFolder(): void
    {
        $this->showCreateFolder = false;
        $this->nuevaCarpetaNombre = '';
        $this->nuevaCarpetaSinRecordatorios = false;
        $this->resetErrorBag(['nuevaCarpetaNombre']);
    }

    public function createFolder(): void
    {
        Gate::authorize('create', LegalDocumentFolder::class);

        $data = $this->validate([
            'nuevaCarpetaNombre' => ['required', 'string', 'min:2', 'max:120'],
            'nuevaCarpetaSinRecordatorios' => ['boolean'],
        ], [], [
            'nuevaCarpetaNombre' => 'nombre del área / carpeta',
        ]);

        $name = trim($data['nuevaCarpetaNombre']);
        $slug = $this->uniqueFolderSlug($name);
        $sort = (int) LegalDocumentFolder::query()->max('sort_order') + 1;

        $folder = LegalDocumentFolder::query()->create([
            'name' => $name,
            'slug' => $slug,
            'exclude_reminders' => (bool) $data['nuevaCarpetaSinRecordatorios'],
            'reminder_every_minutes' => 60,
            'sort_order' => $sort,
            'is_active' => true,
        ]);

        app(\App\Services\LegalDocuments\LegalDocumentActivityLogger::class)->log(
            $folder,
            'folder_created',
            'Creó carpeta «'.$folder->name.'»',
            auth()->user(),
            null,
            ['slug' => $folder->slug],
        );

        $this->assign[$folder->id] = [];
        $this->directorBusqueda[$folder->id] = '';
        $this->cancelCreateFolder();

        session()->flash('success', 'Carpeta «'.$folder->name.'» creada. Asigne responsables y abra la carpeta para agregar documentos.');
    }

    private function uniqueFolderSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        if ($base === '') {
            $base = 'area';
        }
        $base = \Illuminate\Support\Str::limit($base, 70, '');
        $slug = $base;
        $i = 2;
        while (LegalDocumentFolder::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function deleteFolder(int $folderId): void
    {
        $folder = LegalDocumentFolder::query()->findOrFail($folderId);
        Gate::authorize('delete', $folder);

        $name = $folder->name;

        app(\App\Services\LegalDocuments\LegalDocumentActivityLogger::class)->log(
            $folder,
            'folder_deleted',
            'Eliminó carpeta «'.$name.'»',
            auth()->user(),
            null,
            ['slug' => $folder->slug],
        );

        $folder->update(['is_active' => false]);

        unset($this->assign[$folderId], $this->directorBusqueda[$folderId]);

        session()->flash('success', 'Carpeta «'.$name.'» eliminada.');
    }

    public function seleccionarDirector(int $folderId, string $email, string $name = ''): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $list = array_values($this->assign[$folderId] ?? []);
        foreach ($list as $person) {
            if (($person['email'] ?? '') === $email) {
                $this->directorBusqueda[$folderId] = '';

                return;
            }
        }

        $list[] = [
            'email' => $email,
            'name' => trim($name) !== '' ? trim($name) : $this->resolvePersonName($email),
            'is_primary' => $list === [],
        ];
        $this->assign[$folderId] = $list;
        $this->directorBusqueda[$folderId] = '';
        $this->resetErrorBag('assign.'.$folderId);
    }

    public function quitarDirector(int $folderId, string $email = ''): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $email = strtolower(trim($email));
        $list = array_values(array_filter(
            $this->assign[$folderId] ?? [],
            fn ($p) => ($p['email'] ?? '') !== $email,
        ));

        if ($list !== [] && ! collect($list)->contains(fn ($p) => ! empty($p['is_primary']))) {
            $list[0]['is_primary'] = true;
        }

        $this->assign[$folderId] = $list;
    }

    public function marcarDirector(int $folderId, string $email): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $email = strtolower(trim($email));
        $list = array_values($this->assign[$folderId] ?? []);
        foreach ($list as $i => $person) {
            $list[$i]['is_primary'] = ($person['email'] ?? '') === $email;
        }
        $this->assign[$folderId] = $list;
    }

    public function saveResponsible(int $folderId): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $folder = LegalDocumentFolder::query()->findOrFail($folderId);
        $list = array_values($this->assign[$folderId] ?? []);

        if ($list === []) {
            $this->addError('assign.'.$folderId, 'Agregue al menos un director o responsable.');

            return;
        }

        $people = [];
        foreach ($list as $person) {
            $email = strtolower(trim((string) ($person['email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if (! $this->emailIsAssignable($email)) {
                $this->addError('assign.'.$folderId, 'El correo '.$email.' no está en el directorio ni entre usuarios activos.');

                return;
            }

            $people[] = [
                'email' => $email,
                'name' => trim((string) ($person['name'] ?? '')) ?: $this->resolvePersonName($email),
                'user_id' => User::query()->active()->whereRaw('LOWER(email) = ?', [$email])->value('id'),
                'is_primary' => (bool) ($person['is_primary'] ?? false),
            ];
        }

        if ($people === []) {
            $this->addError('assign.'.$folderId, 'Agregue al menos un director o responsable válido.');

            return;
        }

        $before = $folder->responsiblesLabel();
        $folder->syncResponsibles($people);
        $folder->load('responsibles');

        $this->assign[$folderId] = $folder->responsibles->map(fn ($r) => [
            'email' => strtolower((string) $r->email),
            'name' => (string) ($r->name ?: $this->resolvePersonName((string) $r->email)),
            'is_primary' => (bool) $r->is_primary,
        ])->values()->all();

        $this->directorBusqueda[$folderId] = '';
        $this->resetErrorBag('assign.'.$folderId);

        app(\App\Services\LegalDocuments\LegalDocumentActivityLogger::class)->log(
            $folder,
            'responsible_changed',
            'Actualizó responsables: '.$before.' → '.$folder->responsiblesLabel(),
            auth()->user(),
            null,
            ['people' => $this->assign[$folderId]],
        );

        session()->flash('success', 'Responsables de «'.$folder->name.'» actualizados.');
    }

    /**
     * @return Collection<int, array{name: string, email: string}>
     */
    public function resultadosBusqueda(int $folderId): Collection
    {
        $term = trim((string) ($this->directorBusqueda[$folderId] ?? ''));
        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        $fromDirectory = DirectoryUser::query()
            ->active()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['name', 'email'])
            ->map(fn (DirectoryUser $u) => [
                'name' => (string) $u->name,
                'email' => strtolower((string) $u->email),
            ]);

        $fromUsers = User::query()
            ->active()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['name', 'email'])
            ->map(fn (User $u) => [
                'name' => (string) $u->name,
                'email' => strtolower((string) $u->email),
            ]);

        return $fromDirectory
            ->concat($fromUsers)
            ->unique('email')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->take(12);
    }

    private function emailIsAssignable(string $email): bool
    {
        if (DirectoryUser::query()->active()->where('email', $email)->exists()) {
            return true;
        }

        return User::query()->active()->where('email', $email)->exists();
    }

    private function resolvePersonName(string $email): string
    {
        $name = (string) (DirectoryUser::query()->where('email', $email)->value('name') ?? '');
        if ($name !== '') {
            return $name;
        }

        return (string) (User::query()->where('email', $email)->value('name') ?? '');
    }

    public function render()
    {
        $user = auth()->user();
        $canAssign = Gate::allows('assignResponsible', LegalDocumentFolder::class);

        $foldersQuery = LegalDocumentFolder::query()
            ->with(['responsible:id,name,email', 'responsibles'])
            ->withCount([
                'items as items_total' => fn ($q) => $q->where('is_active', true),
                'items as items_due' => fn ($q) => $q->where('is_active', true)
                    ->whereNotNull('renew_on')
                    ->whereDate('renew_on', '<=', now()->toDateString()),
                'items as items_with_file' => fn ($q) => $q->where('is_active', true)->whereHas('currentFile'),
            ])
            ->where('is_active', true);

        // Gestores / abogados ven todas; el director de área solo las de su correo.
        $seesAll = $user && $this->userSeesAllFolders($user);
        if ($user && ! $seesAll) {
            $foldersQuery->visibleTo($user);
        }

        $folders = $foldersQuery
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $resultadosPorCarpeta = [];
        foreach ($folders as $folder) {
            $resultadosPorCarpeta[$folder->id] = $this->resultadosBusqueda($folder->id);
        }

        $canDeleteFolder = $user
            && ! $user->read_only
            && $user->hasPlatformLevel(\App\Enums\PlatformLevel::Nivel1);

        return view('livewire.licitaciones.documentos-legales.index', [
            'folders' => $folders,
            'resultadosPorCarpeta' => $resultadosPorCarpeta,
            'canAssign' => $canAssign,
            'canCreateFolder' => Gate::allows('create', LegalDocumentFolder::class),
            'canDeleteFolder' => $canDeleteFolder,
            'seesAllFolders' => (bool) $seesAll,
        ]);
    }

    private function userSeesAllFolders(User $user): bool
    {
        if ($user->hasPlatformLevel(
            \App\Enums\PlatformLevel::Nivel1,
            \App\Enums\PlatformLevel::Nivel5,
            \App\Enums\PlatformLevel::Nivel6,
        )) {
            return true;
        }

        try {
            return $user->hasPermissionTo('legal-documents.manage')
                || $user->hasPermissionTo('legal-documents.view');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }
}
