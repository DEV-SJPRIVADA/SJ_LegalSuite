<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\CitationEvidenceType;
use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Requisitos de Etapa B (citación) antes de avanzar a diligencia u otros estados.
 */
class DisciplinaryCitationWorkflowService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
    ) {}

    /** @return list<string> */
    public function missingRequirements(DisciplinaryCase $case): array
    {
        $case = $case->fresh(['agendaThread.messages', 'documents']);
        $missing = [];

        if ($case->current_status !== CaseStatus::CITACION_PROGRAMADA) {
            return $missing;
        }

        if ($case->coordination_started_at === null) {
            $missing[] = 'Coordinación con planeación no iniciada.';
        }

        if (! $case->agendaThread?->planning_replied_at) {
            $missing[] = 'Falta respuesta de planeación en el hilo de coordinación.';
        }

        if ($case->citation_confirmed_date === null) {
            $missing[] = 'No se ha seleccionado la fecha definitiva de citación.';
        }

        if ($case->fo_gj_03_generated_at === null) {
            $missing[] = 'No se ha generado el FO-GJ-03 desde el expediente.';
        }

        if ($case->citation_evidence_uploaded_at === null) {
            $missing[] = 'No se ha cargado la evidencia PDF de notificación firmada.';
        }

        return $missing;
    }

    public function assertCanLeaveCitacionStage(DisciplinaryCase $case): void
    {
        $missing = $this->missingRequirements($case);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'citation_workflow' => implode(' ', $missing),
            ]);
        }
    }

    public function readinessChecklist(DisciplinaryCase $case): Collection
    {
        $case = $case->fresh(['agendaThread', 'documents']);

        return collect([
            'coordination_started' => $case->coordination_started_at !== null,
            'planning_replied' => (bool) $case->agendaThread?->planning_replied_at,
            'definitive_date' => $case->citation_confirmed_date !== null,
            'fo_gj_03_generated' => $case->fo_gj_03_generated_at !== null,
            'evidence_uploaded' => $case->citation_evidence_uploaded_at !== null,
        ]);
    }

    /** Etiquetas de requisitos para paneles UX (mismo orden que readinessChecklist). */
    public static function requirementLabels(): array
    {
        return [
            'coordination_started' => 'Coordinación iniciada',
            'planning_replied' => 'Respuesta de Planeación',
            'definitive_date' => 'Fecha definitiva seleccionada',
            'fo_gj_03_generated' => 'FO-GJ-03 generado',
            'evidence_uploaded' => 'Evidencia PDF cargada',
        ];
    }

    public function allRequirementsMet(DisciplinaryCase $case): bool
    {
        return $this->missingRequirements($case) === [];
    }

    public function hasCitationEvidenceDocument(DisciplinaryCase $case): bool
    {
        return $case->documents()
            ->where('document_type', DocumentType::CITACION)
            ->where('notes', 'like', '%evidencia notificación%')
            ->exists();
    }

    public function markEvidenceUploaded(
        DisciplinaryCase $case,
        CitationEvidenceType $type,
    ): DisciplinaryCase {
        $case->forceFill([
            'citation_evidence_type' => $type,
            'citation_evidence_uploaded_at' => now(),
        ])->save();

        return $case->fresh();
    }
}
