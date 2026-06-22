<?php

namespace App\Livewire\Disciplinary\Supervisor;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CitationEvidenceType;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\CitationNotificationSigningService;
use App\Services\Disciplinary\DisciplinaryAuditService;
use App\Services\Disciplinary\DecisionComunicadoService;
use App\Services\Disciplinary\DecisionNotificationSigningService;
use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;
use App\Services\Disciplinary\DisciplinaryDecisionWorkflowService;
use App\Services\Disciplinary\DisciplinaryDocumentService;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Support\Disciplinary\DecisionWorkflowSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Evidencias pendientes')]
class PendingEvidenceIndex extends Component
{
    use WithFileUploads;

    /** @var array<int, string> */
    public array $citationEvidenceTypeByCase = [];

    /** @var array<int, mixed> */
    public array $citationEvidenceFileByCase = [];

    public ?int $evidencePreviewCaseId = null;

    public string $evidencePreviewType = 'signed';

    public ?int $notificationCaseId = null;

    public ?int $decisionNotificationCaseId = null;

    public string $notificationEvidenceType = 'signed';

    public ?string $workerSignatureDataUri = null;

    public ?string $witness1SignatureDataUri = null;

    public ?string $witness2SignatureDataUri = null;

    public string $witness1Name = '';

    public string $witness1Document = '';

    public string $witness2Name = '';

    public string $witness2Document = '';

    public string $signaturePadTarget = 'worker';

    public bool $showSignaturePadModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);
    }

    public function updatedCitationEvidenceFileByCase(mixed $value, string $key): void
    {
        $caseId = (int) $key;
        if ($value === null) {
            return;
        }

        try {
            $this->validate([
                "citationEvidenceFileByCase.{$caseId}" => ['required', 'file', 'mimes:pdf', 'max:15360'],
            ]);
        } catch (ValidationException $e) {
            unset($this->citationEvidenceFileByCase[$caseId]);
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Archivo no válido.');
            }

            return;
        }

        $this->resetErrorBag("citationEvidenceFileByCase.{$caseId}");
        $this->evidencePreviewCaseId = $caseId;
        $this->evidencePreviewType = 'signed';
    }

    public function confirmEvidenceUpload(
        DisciplinaryDocumentService $documents,
        DisciplinaryCitationWorkflowService $citationWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        $caseId = $this->evidencePreviewCaseId;
        if ($caseId === null) {
            return;
        }

        $this->citationEvidenceTypeByCase[$caseId] = $this->evidencePreviewType;

        try {
            try {
                $this->uploadCitationEvidence($caseId, $documents, $citationWorkflow, $audit);
            } catch (ModelNotFoundException) {
                if (! DecisionWorkflowSchema::isReady()) {
                    throw ValidationException::withMessages([
                        'citationEvidenceFileByCase.'.$caseId => 'No se encontró el caso pendiente de evidencia.',
                    ]);
                }

                $this->uploadDecisionEvidence($caseId, $documents, app(DisciplinaryDecisionWorkflowService::class), $audit);
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->evidencePreviewCaseId = null;
    }

    public function cancelEvidenceUpload(): void
    {
        if ($this->evidencePreviewCaseId !== null) {
            unset($this->citationEvidenceFileByCase[$this->evidencePreviewCaseId]);
        }

        $this->evidencePreviewCaseId = null;
        $this->evidencePreviewType = 'signed';
    }

    public function uploadCitationEvidence(
        int $caseId,
        DisciplinaryDocumentService $documents,
        DisciplinaryCitationWorkflowService $citationWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);

        $case = $this->resolveSupervisorPendingCase($caseId);
        Gate::authorize('uploadCitationEvidence', $case);
        $citationWorkflow->assertCitationEvidenceUploadAllowed($case, auth()->user());

        $this->validate([
            "citationEvidenceTypeByCase.{$caseId}" => ['required', Rule::in(['signed', 'refused_witnesses'])],
            "citationEvidenceFileByCase.{$caseId}" => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $type = CitationEvidenceType::from((string) $this->citationEvidenceTypeByCase[$caseId]);
        $stage = $case->stages()
            ->where('stage_type', StageType::CITACION)
            ->orderByDesc('sequence')
            ->first();

        $uploader = auth()->user();
        $doc = $documents->upload(
            $case,
            $this->citationEvidenceFileByCase[$caseId],
            DocumentType::CITACION,
            $uploader,
            $stage,
            DisciplinaryCase::NOTE_CITATION_EVIDENCE_PREFIX.' - '.$type->label(),
        );

        $case = $citationWorkflow->markEvidenceUploaded($case->fresh(), $type);

        $audit->logCase(
            $case,
            $uploader,
            ActionType::EVIDENCIA_CITACION_CARGADA,
            'Evidencia PDF de citacion cargada.',
            [
                'evidence_type' => $type->value,
                'document_id' => $doc->id,
                'uploaded_by' => $uploader->id,
                'uploaded_at' => now()->toIso8601String(),
                'fo_gj_03_document_id' => $case->primaryFoGj03CitationDocument()?->id,
                'source' => 'supervisor_pdf_upload',
            ],
        );

        unset($this->citationEvidenceTypeByCase[$caseId], $this->citationEvidenceFileByCase[$caseId]);
        session()->flash('success', "Evidencia cargada para {$case->case_number}.");
    }

    public function uploadDecisionEvidence(
        int $caseId,
        DisciplinaryDocumentService $documents,
        DisciplinaryDecisionWorkflowService $decisionWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);

        $case = $this->resolveDecisionPendingCase($caseId);
        Gate::authorize('uploadDecisionEvidence', $case);
        $decisionWorkflow->assertDecisionEvidenceUploadAllowed($case, auth()->user());

        $this->validate([
            "citationEvidenceTypeByCase.{$caseId}" => ['required', Rule::in(['signed', 'refused_witnesses'])],
            "citationEvidenceFileByCase.{$caseId}" => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $type = CitationEvidenceType::from((string) $this->citationEvidenceTypeByCase[$caseId]);
        $stage = $case->stages()
            ->where('stage_type', StageType::DECISION)
            ->orderByDesc('sequence')
            ->first();

        $uploader = auth()->user();
        $doc = $documents->upload(
            $case,
            $this->citationEvidenceFileByCase[$caseId],
            DocumentType::DECISION,
            $uploader,
            $stage,
            DisciplinaryCase::NOTE_DECISION_EVIDENCE_PREFIX.' - '.$type->label(),
        );

        $case = $decisionWorkflow->markEvidenceUploaded($case->fresh(), $type);

        $audit->logCase(
            $case,
            $uploader,
            ActionType::DECISION_EVIDENCIA_CARGADA,
            'Evidencia PDF de decisión cargada.',
            [
                'evidence_type' => $type->value,
                'document_id' => $doc->id,
            ],
        );

        unset($this->citationEvidenceTypeByCase[$caseId], $this->citationEvidenceFileByCase[$caseId]);
        session()->flash('success', "Evidencia de decisión cargada para {$case->case_number}.");
    }

    public function openDecisionNotificationModal(int $caseId): void
    {
        if (! DecisionWorkflowSchema::isReady()) {
            $this->addError('decisionNotification', 'La etapa de decisión no está disponible en este entorno.');

            return;
        }

        $case = $this->resolveDecisionPendingCase($caseId);
        Gate::authorize('viewDecisionComunicadoForSupervisor', $case);

        $this->resetErrorBag();
        $this->notificationCaseId = null;
        $this->decisionNotificationCaseId = $caseId;
        $this->resetNotificationCaptureState();
    }

    public function closeDecisionNotificationModal(): void
    {
        $this->decisionNotificationCaseId = null;
        $this->resetNotificationCaptureState();
    }

    public function openNotificationModal(int $caseId): void
    {
        $case = $this->resolveSupervisorPendingCase($caseId);
        Gate::authorize('viewFoGj03NotificationForSupervisor', $case);

        $this->resetErrorBag();
        $this->decisionNotificationCaseId = null;
        $this->notificationCaseId = $caseId;
        $this->resetNotificationCaptureState();
    }

    public function closeNotificationModal(): void
    {
        $this->notificationCaseId = null;
        $this->resetNotificationCaptureState();
    }

    public function uploadSignedDecisionNotification(
        DecisionNotificationSigningService $signing,
        DisciplinaryDocumentService $documents,
        DisciplinaryDecisionWorkflowService $decisionWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        $caseId = $this->decisionNotificationCaseId;
        if ($caseId === null) {
            return;
        }

        $case = $this->resolveDecisionPendingCase($caseId);
        Gate::authorize('uploadDecisionEvidence', $case);
        Gate::authorize('viewDecisionComunicadoForSupervisor', $case);
        $decisionWorkflow->assertDecisionEvidenceUploadAllowed($case, auth()->user());

        try {
            $payload = $signing->validateNotificationPayload([
                'evidence_type' => $this->notificationEvidenceType,
                'worker_signature' => $this->workerSignatureDataUri,
                'witnesses' => [
                    [
                        'signature' => $this->witness1SignatureDataUri,
                        'name' => $this->witness1Name,
                        'document' => $this->witness1Document,
                    ],
                    [
                        'signature' => $this->witness2SignatureDataUri,
                        'name' => $this->witness2Name,
                        'document' => $this->witness2Document,
                    ],
                ],
            ]);
            $binary = $signing->renderNotificationPdf($case, $payload);
            $type = CitationEvidenceType::from($payload['evidence_type']);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'No se pudo generar el documento firmado.');
            }

            return;
        } catch (\Throwable $e) {
            $this->addError('signedDecisionNotification', 'No se pudo generar el PDF firmado. '.$e->getMessage());

            return;
        }

        $filename = $type === CitationEvidenceType::SIGNED
            ? 'FO-GJ-DECISION-firmado-'.$case->case_number.'.pdf'
            : 'FO-GJ-DECISION-rechazo-testigos-'.$case->case_number.'.pdf';

        $path = tempnam(sys_get_temp_dir(), 'decision_signed_');
        file_put_contents($path, $binary);

        try {
            $uploaded = new UploadedFile($path, $filename, 'application/pdf', UPLOAD_ERR_OK, true);

            $stage = $case->stages()
                ->where('stage_type', StageType::DECISION)
                ->orderByDesc('sequence')
                ->first();

            $uploader = auth()->user();
            $doc = $documents->upload(
                $case,
                $uploaded,
                DocumentType::DECISION,
                $uploader,
                $stage,
                DisciplinaryCase::NOTE_DECISION_EVIDENCE_PREFIX.' - '.$type->label(),
            );

            $case = $decisionWorkflow->markEvidenceUploaded($case->fresh(), $type);

            $audit->logCase(
                $case,
                $uploader,
                ActionType::DECISION_EVIDENCIA_CARGADA,
                $type === CitationEvidenceType::SIGNED
                    ? 'Comunicado de decisión firmado digitalmente por el trabajador.'
                    : 'Comunicado de decisión con rechazo de firma y testigos.',
                [
                    'evidence_type' => $type->value,
                    'document_id' => $doc->id,
                    'source' => 'supervisor_html_signature',
                ],
            );
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->closeDecisionNotificationModal();
        session()->flash('success', "Notificación de decisión cargada para {$case->case_number}.");
    }

    public function updatedNotificationEvidenceType(): void
    {
        $this->resetNotificationCaptureState(keepType: true);
    }

    public function openWorkerSignaturePad(): void
    {
        if (($this->notificationCaseId === null && $this->decisionNotificationCaseId === null)
            || $this->notificationEvidenceType !== 'signed') {
            return;
        }

        $this->signaturePadTarget = 'worker';
        $this->showSignaturePadModal = true;
    }

    public function openWitnessSignaturePad(int $witness): void
    {
        if (($this->notificationCaseId === null && $this->decisionNotificationCaseId === null)
            || $this->notificationEvidenceType !== 'refused_witnesses') {
            return;
        }

        $this->signaturePadTarget = match ($witness) {
            2 => 'witness2',
            default => 'witness1',
        };
        $this->showSignaturePadModal = true;
    }

    public function closeWorkerSignaturePad(): void
    {
        $this->showSignaturePadModal = false;
    }

    public function saveCapturedSignature(
        string $dataUri,
        CitationNotificationSigningService $citationSigning,
        DecisionNotificationSigningService $decisionSigning,
    ): void {
        if ($this->notificationCaseId === null && $this->decisionNotificationCaseId === null) {
            return;
        }

        $signing = $this->decisionNotificationCaseId !== null ? $decisionSigning : $citationSigning;

        $field = match ($this->signaturePadTarget) {
            'witness1' => 'witness1Signature',
            'witness2' => 'witness2Signature',
            default => 'workerSignature',
        };

        $message = match ($this->signaturePadTarget) {
            'witness1' => 'Capture la firma del testigo 1.',
            'witness2' => 'Capture la firma del testigo 2.',
            default => 'Capture la firma del trabajador antes de cargar el documento firmado.',
        };

        try {
            $valid = $signing->assertValidSignatureDataUri($dataUri, $field, $message);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $errorField => $messages) {
                $this->addError($errorField, $messages[0] ?? 'Firma no válida.');
            }

            return;
        }

        match ($this->signaturePadTarget) {
            'witness1' => $this->witness1SignatureDataUri = $valid,
            'witness2' => $this->witness2SignatureDataUri = $valid,
            default => $this->workerSignatureDataUri = $valid,
        };

        $this->showSignaturePadModal = false;
        $this->resetErrorBag($field);
    }

    public function saveWorkerSignature(
        string $dataUri,
        CitationNotificationSigningService $citationSigning,
        DecisionNotificationSigningService $decisionSigning,
    ): void {
        $this->signaturePadTarget = 'worker';
        $this->saveCapturedSignature($dataUri, $citationSigning, $decisionSigning);
    }

    public function clearWorkerSignature(): void
    {
        $this->workerSignatureDataUri = null;
    }

    public function clearWitnessSignature(int $witness): void
    {
        match ($witness) {
            2 => $this->witness2SignatureDataUri = null,
            default => $this->witness1SignatureDataUri = null,
        };
    }

    public function notificationUploadReady(): bool
    {
        if ($this->notificationEvidenceType === 'signed') {
            return filled($this->workerSignatureDataUri);
        }

        return filled($this->witness1SignatureDataUri)
            && filled($this->witness2SignatureDataUri)
            && filled(trim($this->witness1Name))
            && filled(trim($this->witness1Document))
            && filled(trim($this->witness2Name))
            && filled(trim($this->witness2Document));
    }

    public function uploadSignedNotification(
        CitationNotificationSigningService $signing,
        DisciplinaryDocumentService $documents,
        DisciplinaryCitationWorkflowService $citationWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        $caseId = $this->notificationCaseId;
        if ($caseId === null) {
            return;
        }

        $case = $this->resolveSupervisorPendingCase($caseId);
        Gate::authorize('uploadCitationEvidence', $case);
        Gate::authorize('viewFoGj03NotificationForSupervisor', $case);
        $citationWorkflow->assertCitationEvidenceUploadAllowed($case, auth()->user());

        try {
            $payload = $signing->validateNotificationPayload([
                'evidence_type' => $this->notificationEvidenceType,
                'worker_signature' => $this->workerSignatureDataUri,
                'witnesses' => [
                    [
                        'signature' => $this->witness1SignatureDataUri,
                        'name' => $this->witness1Name,
                        'document' => $this->witness1Document,
                    ],
                    [
                        'signature' => $this->witness2SignatureDataUri,
                        'name' => $this->witness2Name,
                        'document' => $this->witness2Document,
                    ],
                ],
            ]);
            $binary = $signing->renderNotificationPdf($case, $payload);
            $type = CitationEvidenceType::from($payload['evidence_type']);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'No se pudo generar el documento firmado.');
            }

            return;
        } catch (\Throwable $e) {
            $this->addError('signedNotification', 'No se pudo generar el PDF firmado. '.$e->getMessage());

            return;
        }

        $filename = $type === CitationEvidenceType::SIGNED
            ? 'FO-GJ-03-notificacion-firmada-'.$case->case_number.'.pdf'
            : 'FO-GJ-03-notificacion-rechazo-testigos-'.$case->case_number.'.pdf';

        $path = tempnam(sys_get_temp_dir(), 'fo03_signed_');
        file_put_contents($path, $binary);

        try {
            $uploaded = new UploadedFile(
                $path,
                $filename,
                'application/pdf',
                UPLOAD_ERR_OK,
                true,
            );

            $stage = $case->stages()
                ->where('stage_type', StageType::CITACION)
                ->orderByDesc('sequence')
                ->first();

            $uploader = auth()->user();
            $doc = $documents->upload(
                $case,
                $uploaded,
                DocumentType::CITACION,
                $uploader,
                $stage,
                DisciplinaryCase::NOTE_CITATION_EVIDENCE_PREFIX.' - '.$type->label(),
            );

            $case = $citationWorkflow->markEvidenceUploaded($case->fresh(), $type);

            $audit->logCase(
                $case,
                $uploader,
                ActionType::EVIDENCIA_CITACION_CARGADA,
                $type === CitationEvidenceType::SIGNED
                    ? 'Evidencia PDF de citación firmada digitalmente por el trabajador.'
                    : 'Evidencia PDF de citación con rechazo de firma y testigos.',
                [
                    'evidence_type' => $type->value,
                    'document_id' => $doc->id,
                    'uploaded_by' => $uploader->id,
                    'uploaded_at' => now()->toIso8601String(),
                    'fo_gj_03_document_id' => $case->primaryFoGj03CitationDocument()?->id,
                    'source' => 'supervisor_html_signature',
                    'witnesses' => $type === CitationEvidenceType::REFUSED_WITNESSES ? [
                        ['name' => $this->witness1Name, 'document' => $this->witness1Document],
                        ['name' => $this->witness2Name, 'document' => $this->witness2Document],
                    ] : null,
                ],
            );
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->closeNotificationModal();
        session()->flash('success', "Notificación cargada para {$case->case_number}.");
    }

    public function render(FoGj03CitationService $foGj03, DecisionComunicadoService $decisionComunicado)
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);

        $notificationCase = null;
        $notificationViewData = null;
        $decisionNotificationCase = null;
        $decisionNotificationViewData = null;

        if ($this->notificationCaseId !== null) {
            $notificationCase = $this->resolveSupervisorPendingCase($this->notificationCaseId);
            Gate::authorize('viewFoGj03NotificationForSupervisor', $notificationCase);
            $notificationViewData = array_merge(
                $foGj03->buildViewData($notificationCase),
                $this->buildNotificationPreviewData(),
            );
        }

        if ($this->decisionNotificationCaseId !== null && DecisionWorkflowSchema::isReady()) {
            $decisionNotificationCase = $this->resolveDecisionPendingCase($this->decisionNotificationCaseId);
            Gate::authorize('viewDecisionComunicadoForSupervisor', $decisionNotificationCase);
            $decisionNotificationViewData = array_merge(
                $decisionComunicado->buildViewData($decisionNotificationCase),
                $this->buildNotificationPreviewData(),
            );
        }

        $evidencePreviewUrl = null;
        if ($this->evidencePreviewCaseId !== null) {
            $previewFile = $this->citationEvidenceFileByCase[$this->evidencePreviewCaseId] ?? null;
            if ($previewFile) {
                $evidencePreviewUrl = $previewFile->temporaryUrl();
            } else {
                $this->evidencePreviewCaseId = null;
            }
        }

        $tasks = DisciplinaryCase::query()
            ->where('notification_supervisor_user_id', auth()->id())
            ->whereNotNull('fo_gj_03_generated_at')
            ->whereNull('citation_evidence_uploaded_at')
            ->whereHas('documents', fn ($q) => $q
                ->where('document_type', DocumentType::CITACION)
                ->where('notes', 'like', '%'.DisciplinaryCase::NOTE_FO_GJ_03_GENERATED.'%'))
            ->with(['employee:id,first_name,last_name'])
            ->orderByDesc('fo_gj_03_generated_at')
            ->get();

        $decisionTasks = DecisionWorkflowSchema::isReady()
            ? DisciplinaryCase::query()
                ->where('decision_notification_supervisor_user_id', auth()->id())
                ->whereNotNull('decision_comunicado_generated_at')
                ->whereNull('decision_evidence_uploaded_at')
                ->with(['employee:id,first_name,last_name'])
                ->orderByDesc('decision_comunicado_generated_at')
                ->get()
            : collect();

        return view('livewire.disciplinary.supervisor.pending-evidence-index', [
            'tasks' => $tasks,
            'decisionTasks' => $decisionTasks,
            'notificationCase' => $notificationCase,
            'notificationViewData' => $notificationViewData,
            'decisionNotificationCase' => $decisionNotificationCase,
            'decisionNotificationViewData' => $decisionNotificationViewData,
            'decisionNotificationCaseId' => $this->decisionNotificationCaseId,
            'evidencePreviewUrl' => $evidencePreviewUrl,
        ]);
    }

    /** @return array<string, mixed> */
    private function buildNotificationPreviewData(): array
    {
        return [
            'evidenceType' => $this->notificationEvidenceType,
            'workerSignatureDataUri' => $this->notificationEvidenceType === 'signed'
                ? $this->workerSignatureDataUri
                : null,
            'witnesses' => $this->notificationEvidenceType === 'refused_witnesses'
                ? [
                    [
                        'signatureDataUri' => $this->witness1SignatureDataUri,
                        'name' => $this->witness1Name,
                        'document' => $this->witness1Document,
                    ],
                    [
                        'signatureDataUri' => $this->witness2SignatureDataUri,
                        'name' => $this->witness2Name,
                        'document' => $this->witness2Document,
                    ],
                ]
                : [],
        ];
    }

    private function resetNotificationCaptureState(bool $keepType = false): void
    {
        if (! $keepType) {
            $this->notificationEvidenceType = 'signed';
        }

        $this->workerSignatureDataUri = null;
        $this->witness1SignatureDataUri = null;
        $this->witness2SignatureDataUri = null;
        $this->witness1Name = '';
        $this->witness1Document = '';
        $this->witness2Name = '';
        $this->witness2Document = '';
        $this->signaturePadTarget = 'worker';
        $this->showSignaturePadModal = false;
    }

    private function resolveSupervisorPendingCase(int $caseId): DisciplinaryCase
    {
        return DisciplinaryCase::query()
            ->with(['employee', 'assignedLawyer.jobPosition', 'informeSubmission', 'documents', 'stages'])
            ->whereKey($caseId)
            ->where('notification_supervisor_user_id', auth()->id())
            ->whereNotNull('fo_gj_03_generated_at')
            ->whereNull('citation_evidence_uploaded_at')
            ->firstOrFail();
    }

    private function resolveDecisionPendingCase(int $caseId): DisciplinaryCase
    {
        return DisciplinaryCase::query()
            ->with(['employee', 'assignedLawyer.jobPosition', 'informeSubmission', 'documents', 'stages'])
            ->whereKey($caseId)
            ->where('decision_notification_supervisor_user_id', auth()->id())
            ->whereNotNull('decision_comunicado_generated_at')
            ->whereNull('decision_evidence_uploaded_at')
            ->firstOrFail();
    }
}
