<?php

namespace App\Livewire\Licitaciones\DocumentosLegales;

use App\Models\Directory\DirectoryUser;
use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Documentos Legales')]
class DocumentosLegalesIndex extends Component
{
    /** @var array<int, array{email: string}> */
    public array $assign = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', LegalDocumentFolder::class);

        LegalDocumentFolder::query()->where('is_active', true)->get(['id', 'responsible_user_id', 'responsible_email'])
            ->each(function (LegalDocumentFolder $folder) {
                $email = (string) ($folder->responsible_email ?? '');
                if ($email === '' && $folder->responsible_user_id) {
                    $email = (string) (User::query()->whereKey($folder->responsible_user_id)->value('email') ?? '');
                }

                $this->assign[$folder->id] = [
                    'email' => strtolower($email),
                ];
            });
    }

    public function saveResponsible(int $folderId): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $folder = LegalDocumentFolder::query()->findOrFail($folderId);
        $email = strtolower(trim((string) ($this->assign[$folderId]['email'] ?? '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('assign.'.$folderId.'.email', 'Seleccione un correo del directorio.');

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

        $this->resetErrorBag('assign.'.$folderId.'.email');
        session()->flash('success', 'Responsable de «'.$folder->name.'» actualizado.');
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

        $directoryPeople = DirectoryUser::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $directoryByEmail = $directoryPeople->keyBy(fn (DirectoryUser $u) => strtolower($u->email));

        return view('livewire.licitaciones.documentos-legales.index', [
            'folders' => $folders,
            'directoryPeople' => $directoryPeople,
            'directoryByEmail' => $directoryByEmail,
            'canAssign' => Gate::allows('assignResponsible', LegalDocumentFolder::class),
        ]);
    }
}
