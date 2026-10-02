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
    /** @var array<int, array{email: string, name: string}> */
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
        LegalDocumentFolder::query()->where('is_active', true)->get(['id', 'responsible_user_id', 'responsible_email'])
            ->each(function (LegalDocumentFolder $folder) {
                $email = (string) ($folder->responsible_email ?? '');
                if ($email === '' && $folder->responsible_user_id) {
                    $email = (string) (User::query()->whereKey($folder->responsible_user_id)->value('email') ?? '');
                }
                $email = strtolower($email);
                $name = $email !== '' ? $this->resolvePersonName($email) : '';

                $this->assign[$folder->id] = [
                    'email' => $email,
                    'name' => $name,
                ];
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

        $this->assign[$folder->id] = ['email' => '', 'name' => ''];
        $this->directorBusqueda[$folder->id] = '';
        $this->cancelCreateFolder();

        session()->flash('success', 'Carpeta «'.$folder->name.'» creada. Asigne un director y abra la carpeta para agregar documentos.');
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

    public function seleccionarDirector(int $folderId, string $email, string $name = ''): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->assign[$folderId] = [
            'email' => $email,
            'name' => trim($name),
        ];
        $this->directorBusqueda[$folderId] = '';
        $this->resetErrorBag('assign.'.$folderId.'.email');
    }

    public function quitarDirector(int $folderId): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $this->assign[$folderId] = [
            'email' => '',
            'name' => '',
        ];
    }

    public function saveResponsible(int $folderId): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $folder = LegalDocumentFolder::query()->findOrFail($folderId);
        $email = strtolower(trim((string) ($this->assign[$folderId]['email'] ?? '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('assign.'.$folderId.'.email', 'Busque y seleccione una persona del directorio o un usuario del sistema.');

            return;
        }

        if (! $this->emailIsAssignable($email)) {
            $this->addError('assign.'.$folderId.'.email', 'El correo no está en el directorio corporativo ni entre usuarios activos del sistema.');

            return;
        }

        $localUserId = User::query()
            ->active()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->value('id');
        $before = (string) ($folder->responsible_email ?: 'sin asignar');

        $folder->update([
            'responsible_user_id' => $localUserId ?: null,
            'responsible_email' => $email,
        ]);

        $name = $this->resolvePersonName($email);
        $this->assign[$folderId]['name'] = $name;
        $this->directorBusqueda[$folderId] = '';
        $this->resetErrorBag('assign.'.$folderId.'.email');

        app(\App\Services\LegalDocuments\LegalDocumentActivityLogger::class)->log(
            $folder,
            'responsible_changed',
            'Asignó director: '.$before.' → '.$email.($name !== '' ? ' ('.$name.')' : ''),
            auth()->user(),
            null,
            ['email' => $email, 'name' => $name],
        );

        session()->flash('success', 'Responsable de «'.$folder->name.'» actualizado.');
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
            ->with('responsible:id,name,email')
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

        return view('livewire.licitaciones.documentos-legales.index', [
            'folders' => $folders,
            'resultadosPorCarpeta' => $resultadosPorCarpeta,
            'canAssign' => $canAssign,
            'canCreateFolder' => Gate::allows('create', LegalDocumentFolder::class),
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
