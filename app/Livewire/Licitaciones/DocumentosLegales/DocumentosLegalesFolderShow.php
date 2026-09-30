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

    public string $nuevaExpedicion = '';

    public string $nuevasObservaciones = '';

    public ?int $uploadItemId = null;

    public $uploadFile = null;

    public ?int $editingIssuedItemId = null;

    public string $editingIssuedDate = '';

    public ?int $editingFrequencyItemId = null;

    public string $editingFrequency = '';

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
        $this->cancelEditIssued();
        $this->cancelEditFrequency();
        $this->uploadItemId = $itemId;
        $this->reset('uploadFile');
        $this->resetErrorBag('uploadFile');
    }

    public function cancelUpload(): void
    {
        $this->reset('uploadItemId', 'uploadFile');
        $this->resetErrorBag('uploadFile');
    }

    public function startEditIssued(int $itemId): void
    {
        Gate::authorize('editRules', $this->folder);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($itemId)
            ->firstOrFail();

        $this->cancelUpload();
        $this->cancelEditFrequency();
        $this->editingIssuedItemId = $item->id;
        $this->editingIssuedDate = $item->issued_on?->format('Y-m-d') ?? '';
        $this->resetErrorBag('editingIssuedDate');
    }

    public function cancelEditIssued(): void
    {
        $this->reset('editingIssuedItemId', 'editingIssuedDate');
        $this->resetErrorBag('editingIssuedDate');
    }

    public function startEditFrequency(int $itemId): void
    {
        Gate::authorize('editRules', $this->folder);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($itemId)
            ->firstOrFail();

        $this->cancelUpload();
        $this->cancelEditIssued();
        $this->editingFrequencyItemId = $item->id;
        $this->editingFrequency = (string) ($item->frequency ?? '');
        $this->resetErrorBag('editingFrequency');
    }

    public function cancelEditFrequency(): void
    {
        $this->reset('editingFrequencyItemId', 'editingFrequency');
        $this->resetErrorBag('editingFrequency');
    }

    public function saveFrequency(LegalDocumentActivityLogger $logger): void
    {
        Gate::authorize('editRules', $this->folder);

        $data = $this->validate([
            'editingFrequencyItemId' => ['required', 'integer'],
            'editingFrequency' => ['nullable', 'string', 'max:80'],
        ], [], [
            'editingFrequency' => 'frecuencia',
        ]);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($data['editingFrequencyItemId'])
            ->firstOrFail();

        $beforeFreq = $item->frequency ?: '—';
        $beforeRenew = $item->renew_on?->format('d/m/Y') ?: '—';
        $newFrequency = trim((string) ($data['editingFrequency'] ?? ''));
        $newFrequency = $newFrequency !== '' ? $newFrequency : null;

        $next = $item->syncRenewalFromIssued(
            $item->issued_on?->toDateString(),
            $newFrequency,
        );

        $logger->log(
            $this->folder,
            'frequency_changed',
            'Cambió frecuencia de «'.$item->displayTitle().'»: '.$beforeFreq.' → '.($newFrequency ?: '—')
            .' · renueva '.$beforeRenew.' → '.($next?->format('d/m/Y') ?: '—'),
            auth()->user(),
            $item,
            [
                'frequency_before' => $beforeFreq,
                'frequency_after' => $newFrequency,
                'renew_on' => $next?->toDateString(),
            ],
        );

        $this->cancelEditFrequency();
        $msg = 'Frecuencia actualizada.';
        if ($next) {
            $msg .= ' Debe renovarse el '.$next->format('d/m/Y').'.';
        } elseif (! $item->issued_on) {
            $msg .= ' Indique también la fecha de expedición para calcular la renovación.';
        }
        session()->flash('success', $msg);
    }

    public function saveIssuedDate(LegalDocumentActivityLogger $logger): void
    {
        Gate::authorize('editRules', $this->folder);

        $data = $this->validate([
            'editingIssuedItemId' => ['required', 'integer'],
            'editingIssuedDate' => ['nullable', 'date'],
        ], [], [
            'editingIssuedDate' => 'fecha de expedición',
        ]);

        $item = LegalDocumentItem::query()
            ->where('folder_id', $this->folder->id)
            ->whereKey($data['editingIssuedItemId'])
            ->firstOrFail();

        $beforeIssued = $item->issued_on?->format('d/m/Y') ?: '—';
        $beforeRenew = $item->renew_on?->format('d/m/Y') ?: '—';
        $newIssued = $data['editingIssuedDate'] !== '' && $data['editingIssuedDate'] !== null
            ? $data['editingIssuedDate']
            : null;

        $next = $item->syncRenewalFromIssued($newIssued, $item->frequency);

        $logger->log(
            $this->folder,
            'issued_date_changed',
            'Cambió expedición de «'.$item->displayTitle().'»: '.$beforeIssued.' → '.($newIssued ? \Illuminate\Support\Carbon::parse($newIssued)->format('d/m/Y') : '—')
            .' · renueva '.$beforeRenew.' → '.($next?->format('d/m/Y') ?: '—'),
            auth()->user(),
            $item,
            [
                'issued_before' => $beforeIssued,
                'issued_after' => $newIssued,
                'renew_on' => $next?->toDateString(),
            ],
        );

        $this->cancelEditIssued();
        $msg = 'Fecha de expedición guardada.';
        if ($next) {
            $msg .= ' Renovación calculada: '.$next->format('d/m/Y').'. Los avisos inician ese día si no hay archivo nuevo.';
        } elseif (! $item->frequency) {
            $msg .= ' Defina la frecuencia (MENSUAL, ANUAL…) para calcular cuándo renovar.';
        }
        session()->flash('success', $msg);
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

        $item->refresh();
        $this->reset('uploadItemId', 'uploadFile');

        $next = $item->renew_on?->format('d/m/Y');
        $issued = $item->issued_on?->format('d/m/Y');
        $message = 'Documento actualizado. La versión anterior fue descartada y las alertas se detienen.';
        if ($issued && $next) {
            $message .= ' Expedición: '.$issued.' · Renueva el '.$next
                .($item->frequency ? ' ('.$item->frequency.')' : '').'.';
        } elseif (! $item->frequency) {
            $message .= ' Defina una frecuencia (MENSUAL, 6 MESES…) para programar la próxima renovación.';
        }

        session()->flash('success', $message);
    }

    public function agregarSolicitud(LegalDocumentService $service): void
    {
        Gate::authorize('addRequest', $this->folder);

        $data = $this->validate([
            'nuevoTitulo' => ['required', 'string', 'max:255'],
            'nuevaFrecuencia' => ['nullable', 'string', 'max:80'],
            'nuevaExpedicion' => ['nullable', 'date'],
            'nuevasObservaciones' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'nuevoTitulo' => 'nombre del documento',
            'nuevaExpedicion' => 'fecha de expedición',
        ]);

        $service->addExtraRequest(
            $this->folder,
            auth()->user(),
            $data['nuevoTitulo'],
            $data['nuevaFrecuencia'] !== '' ? $data['nuevaFrecuencia'] : null,
            $data['nuevaExpedicion'] !== '' ? $data['nuevaExpedicion'] : null,
            $data['nuevasObservaciones'] !== '' ? $data['nuevasObservaciones'] : null,
        );

        $this->reset('nuevoTitulo', 'nuevaFrecuencia', 'nuevaExpedicion', 'nuevasObservaciones');
        session()->flash('success', 'Solicitud de documento agregada. La renovación se calcula con expedición + frecuencia.');
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
