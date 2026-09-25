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

    public function mount(): void
    {
        Gate::authorize('viewAny', LegalDocumentFolder::class);

        LegalDocumentFolder::query()->where('is_active', true)->get(['id', 'responsible_user_id', 'responsible_email'])
            ->each(function (LegalDocumentFolder $folder) {
                $email = (string) ($folder->responsible_email ?? '');
                if ($email === '' && $folder->responsible_user_id) {
                    $email = (string) (User::query()->whereKey($folder->responsible_user_id)->value('email') ?? '');
                }
                $email = strtolower($email);
                $name = '';
                if ($email !== '') {
                    $name = (string) (DirectoryUser::query()->where('email', $email)->value('name') ?? '');
                }

                $this->assign[$folder->id] = [
                    'email' => $email,
                    'name' => $name,
                ];
                $this->directorBusqueda[$folder->id] = '';
            });
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
            $this->addError('assign.'.$folderId.'.email', 'Busque y seleccione una persona del directorio.');

            return;
        }

        $inDirectory = DirectoryUser::query()->active()->where('email', $email)->exists();
        if (! $inDirectory) {
            $this->addError('assign.'.$folderId.'.email', 'El correo no está en el directorio corporativo.');

            return;
        }

        $localUserId = User::query()->active()->where('email', $email)->value('id');

        $folder->update([
            'responsible_user_id' => $localUserId ?: null,
            'responsible_email' => $email,
        ]);

        $name = (string) (DirectoryUser::query()->where('email', $email)->value('name') ?? '');
        $this->assign[$folderId]['name'] = $name;
        $this->directorBusqueda[$folderId] = '';
        $this->resetErrorBag('assign.'.$folderId.'.email');
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

        return DirectoryUser::query()
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
    }

    public function render()
    {
        $folders = LegalDocumentFolder::query()
            ->with('responsible:id,name,email')
            ->withCount([
                'items as items_total' => fn ($q) => $q->where('is_active', true),
                'items as items_due' => fn ($q) => $q->where('is_active', true)
                    ->whereNotNull('renew_on')
                    ->whereDate('renew_on', '<=', now()->toDateString()),
                'items as items_with_file' => fn ($q) => $q->where('is_active', true)->whereHas('currentFile'),
            ])
            ->where('is_active', true)
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
            'canAssign' => Gate::allows('assignResponsible', LegalDocumentFolder::class),
        ]);
    }
}
