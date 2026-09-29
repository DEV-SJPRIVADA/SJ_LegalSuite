<?php

namespace App\Livewire\Licitaciones\DocumentosLegales;

use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\LegalDocuments\LegalDocumentItem;
use App\Services\LegalDocuments\LegalDocumentService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Carpeta · Documentos Legales')]
class DocumentosLegalesFolderShow extends Component
{
    use WithFileUploads;

    public LegalDocumentFolder $folder;

    public string $nuevoTitulo = '';

    public string $nuevaFrecuencia = '';

    public string $nuevaRenovacion = '';

    public string $nuevasObservaciones = '';

    public ?int $uploadItemId = null;

    public $uploadFile = null;

    public ?int $editingRenewItemId = null;

    public string $editingRenewDate = '';

    public function mount(LegalDocumentFolder $folder): void
    {
        Gate::authorize('view', $folder);
        $this->folder = $folder;
    }

    public function startUpload(int $itemId): void
    {
        Gate::authorize('upload', $this->folder);
        $this->cancelEditRenew();
        $this->uploadItemId = $itemId;
        $this->reset('uploadFile');
        $this->resetErrorBag('uploadFile');
    }

    public function cancelUpload(): void
    {
        $this->reset('uploadItemId', 'uploadFile');
        $this->resetErrorBag('uploadFile');
    }

    public function startEditRenew(int $itemId): void
    {
        Gate::authorize('upload', $this->folder);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($itemId)
            ->firstOrFail();

        $this->cancelUpload();
        $this->editingRenewItemId = $item->id;
        $this->editingRenewDate = $item->renew_on?->format('Y-m-d') ?? '';
        $this->resetErrorBag('editingRenewDate');
    }

    public function cancelEditRenew(): void
    {
        $this->reset('editingRenewItemId', 'editingRenewDate');
        $this->resetErrorBag('editingRenewDate');
    }

    public function saveRenewDate(): void
    {
        Gate::authorize('upload', $this->folder);

        $data = $this->validate([
            'editingRenewItemId' => ['required', 'integer'],
            'editingRenewDate' => ['nullable', 'date'],
        ], [], [
            'editingRenewDate' => 'fecha de renovación',
        ]);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($data['editingRenewItemId'])
            ->firstOrFail();

        $item->update([
            'renew_on' => $data['editingRenewDate'] !== '' && $data['editingRenewDate'] !== null
                ? $data['editingRenewDate']
                : null,
            'renew_label' => null,
            'last_reminder_at' => null,
        ]);

        $this->cancelEditRenew();
        session()->flash('success', 'Fecha de renovación actualizada. Los recordatorios usarán esa fecha.');
    }

    public function confirmUpload(LegalDocumentService $service): void
    {
        Gate::authorize('upload', $this->folder);

        $this->validate([
            'uploadItemId' => ['required', 'integer'],
            'uploadFile' => ['required', 'file', 'max:20480'],
        ], [], [
            'uploadFile' => 'archivo',
        ]);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($this->uploadItemId)
            ->firstOrFail();

        $service->replaceFile($item, $this->uploadFile, auth()->user());

        // Al renovar el archivo se detienen las alertas de este ciclo.
        $item->update(['last_reminder_at' => null]);

        $this->reset('uploadItemId', 'uploadFile');

        session()->flash('success', 'Documento actualizado. La versión anterior fue descartada y las alertas se detienen.');
    }

    public function agregarSolicitud(LegalDocumentService $service): void
    {
        Gate::authorize('addRequest', $this->folder);

        $data = $this->validate([
            'nuevoTitulo' => ['required', 'string', 'max:255'],
            'nuevaFrecuencia' => ['nullable', 'string', 'max:80'],
            'nuevaRenovacion' => ['nullable', 'date'],
            'nuevasObservaciones' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'nuevoTitulo' => 'nombre del documento',
            'nuevaRenovacion' => 'fecha de renovación',
        ]);

        $service->addExtraRequest(
            $this->folder,
            auth()->user(),
            $data['nuevoTitulo'],
            $data['nuevaFrecuencia'] !== '' ? $data['nuevaFrecuencia'] : null,
            $data['nuevaRenovacion'] !== '' ? $data['nuevaRenovacion'] : null,
            $data['nuevasObservaciones'] !== '' ? $data['nuevasObservaciones'] : null,
        );

        $this->reset('nuevoTitulo', 'nuevaFrecuencia', 'nuevaRenovacion', 'nuevasObservaciones');
        session()->flash('success', 'Solicitud de documento agregada a la carpeta.');
    }

    public function render()
    {
        $items = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->active()
            ->with(['currentFile.uploadedBy:id,name'])
            ->orderByRaw('CASE WHEN renew_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('renew_on')
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        return view('livewire.licitaciones.documentos-legales.folder-show', [
            'items' => $items,
            'canUpload' => Gate::allows('upload', $this->folder),
        ]);
    }
}
