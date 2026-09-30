<?php

namespace App\Livewire\Licitaciones\DocumentosLegales;

use App\Models\LegalDocuments\LegalDocumentActivity;
use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\LegalDocuments\LegalDocumentItem;
use App\Services\LegalDocuments\LegalDocumentActivityLogger;
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

    public int $reminderEveryMinutes = 60;

    public function mount(LegalDocumentFolder $folder): void
    {
        Gate::authorize('view', $folder);
        $this->folder = $folder;
        $this->reminderEveryMinutes = $folder->reminderIntervalMinutes();
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
        Gate::authorize('editRules', $this->folder);

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

    public function saveRenewDate(LegalDocumentActivityLogger $logger): void
    {
        Gate::authorize('editRules', $this->folder);

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

        $before = $item->renew_on?->format('d/m/Y') ?: '—';
        $newDate = $data['editingRenewDate'] !== '' && $data['editingRenewDate'] !== null
            ? $data['editingRenewDate']
            : null;

        $item->update([
            'renew_on' => $newDate,
            'renew_label' => null,
            'last_reminder_at' => null,
        ]);

        $after = $newDate ? \Illuminate\Support\Carbon::parse($newDate)->format('d/m/Y') : '—';

        $logger->log(
            $this->folder,
            'renew_date_changed',
            'Cambió fecha de renovación de «'.$item->displayTitle().'»: '.$before.' → '.$after,
            auth()->user(),
            $item,
            ['before' => $before, 'after' => $after],
        );

        $this->cancelEditRenew();
        session()->flash('success', 'Fecha de renovación actualizada. Los recordatorios usarán esa fecha.');
    }

    public function saveReminderInterval(LegalDocumentActivityLogger $logger): void
    {
        Gate::authorize('manageReminders', $this->folder);

        $allowed = array_keys(LegalDocumentFolder::REMINDER_INTERVAL_OPTIONS);
        $data = $this->validate([
            'reminderEveryMinutes' => ['required', 'integer', 'in:'.implode(',', $allowed)],
        ], [], [
            'reminderEveryMinutes' => 'frecuencia de recordatorio',
        ]);

        $before = $this->folder->reminderIntervalLabel();
        $this->folder->update([
            'reminder_every_minutes' => (int) $data['reminderEveryMinutes'],
        ]);
        $this->folder->refresh();
        $this->reminderEveryMinutes = $this->folder->reminderIntervalMinutes();

        // La nueva frecuencia aplica de inmediato: reinicia el ciclo de los docs pendientes.
        LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->active()
            ->whereNotNull('renew_on')
            ->whereDate('renew_on', '<=', now()->toDateString())
            ->update(['last_reminder_at' => null]);

        $logger->log(
            $this->folder,
            'reminder_interval_changed',
            'Cambió frecuencia de recordatorios: '.$before.' → '.$this->folder->reminderIntervalLabel(),
            auth()->user(),
            null,
            [
                'minutes' => $this->reminderEveryMinutes,
            ],
        );

        $sentNote = '';
        if ($this->folder->reminderRecipients() !== []) {
            try {
                \Illuminate\Support\Facades\Artisan::call('legal-documents:enviar-recordatorios', [
                    '--folder' => (string) $this->folder->id,
                    '--force' => true,
                ]);
                $sentNote = ' Se envió un aviso ahora con la nueva frecuencia.';
            } catch (\Throwable $e) {
                report($e);
                $sentNote = ' No se pudo enviar el aviso inmediato: revise la configuración de correo.';
            }
        } else {
            $sentNote = ' Asigne un director para que salgan los correos.';
        }

        session()->flash(
            'success',
            'Frecuencia actualizada: '.$this->folder->reminderIntervalLabel().'.'
            .' Los próximos avisos usarán ese intervalo.'.$sentNote
        );
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
        $this->folder->refresh();

        $items = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->active()
            ->with(['currentFile.uploadedBy:id,name'])
            ->orderByRaw('CASE WHEN renew_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('renew_on')
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        $activities = LegalDocumentActivity::query()
            ->where('folder_id', $this->folder->id)
            ->with(['user:id,name', 'item:id,title,group_title,code'])
            ->latest('id')
            ->limit(40)
            ->get();

        return view('livewire.licitaciones.documentos-legales.folder-show', [
            'items' => $items,
            'activities' => $activities,
            'canUpload' => Gate::allows('upload', $this->folder),
            'canAddRequest' => Gate::allows('addRequest', $this->folder),
            'canManageReminders' => Gate::allows('manageReminders', $this->folder),
            'canEditRules' => Gate::allows('editRules', $this->folder),
            'reminderOptions' => LegalDocumentFolder::REMINDER_INTERVAL_OPTIONS,
        ]);
    }
}
