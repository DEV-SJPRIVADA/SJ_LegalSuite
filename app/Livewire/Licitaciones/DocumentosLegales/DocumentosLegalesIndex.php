<?php

namespace App\Livewire\Licitaciones\DocumentosLegales;

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
    /** @var array<int, array{user_id: string, email: string}> */
    public array $assign = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', LegalDocumentFolder::class);

        LegalDocumentFolder::query()->where('is_active', true)->get(['id', 'responsible_user_id', 'responsible_email'])
            ->each(function (LegalDocumentFolder $folder) {
                $this->assign[$folder->id] = [
                    'user_id' => $folder->responsible_user_id ? (string) $folder->responsible_user_id : '',
                    'email' => (string) ($folder->responsible_email ?? ''),
                ];
            });
    }

    public function saveResponsible(int $folderId): void
    {
        Gate::authorize('assignResponsible', LegalDocumentFolder::class);

        $folder = LegalDocumentFolder::query()->findOrFail($folderId);
        $row = $this->assign[$folderId] ?? ['user_id' => '', 'email' => ''];
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $userId = (int) ($row['user_id'] ?? 0);

        $folder->update([
            'responsible_user_id' => $userId > 0 ? $userId : null,
            'responsible_email' => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
        ]);

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

        $directors = User::query()
            ->active()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);

        return view('livewire.licitaciones.documentos-legales.index', [
            'folders' => $folders,
            'directors' => $directors,
            'canAssign' => Gate::allows('assignResponsible', LegalDocumentFolder::class),
        ]);
    }
}
