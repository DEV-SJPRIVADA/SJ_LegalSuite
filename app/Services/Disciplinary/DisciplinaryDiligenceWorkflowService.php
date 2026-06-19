<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Validation\ValidationException;

class DisciplinaryDiligenceWorkflowService
{
    public function __construct(
        private readonly DiligenceAttendanceService $attendance,
    ) {}

    /** @return list<string> */
    public function missingAdvanceToDecisionRequirements(DisciplinaryCase $case): array
    {
        return match ($case->current_status) {
            CaseStatus::DILIGENCIA => $this->missingAdvanceFromDiligenciaRequirements($case),
            CaseStatus::COMITE_DISCIPLINARIO => $this->missingAdvanceFromComiteRequirements($case),
            default => ['El expediente debe estar en diligencia o comité disciplinario'],
        };
    }

    /** @return list<string> */
    private function missingAdvanceFromDiligenciaRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($this->attendance->attendance($case) !== DiligenceAttendance::ATTENDED) {
            $missing[] = 'Registro de asistencia: el trabajador debe haber asistido';
        }

        if ($case->fo_gj_04_generated_at === null) {
            $missing[] = 'FO-GJ-04 generado y guardado en el expediente';
        }

        $payload = $case->fo_gj_04_payload ?? [];
        if (! filled($payload['worker_signature_data_uri'] ?? null)) {
            $missing[] = 'Firma del trabajador en el acta FO-GJ-04';
        }

        return $missing;
    }

    /** @return list<string> */
    private function missingAdvanceFromComiteRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($case->comite_generated_at === null && $case->latestComiteActaDocument() === null) {
            $missing[] = 'Acta de comité generada y guardada en el expediente';
        }

        return $missing;
    }

    public function assertCanAdvanceToDecision(DisciplinaryCase $case): void
    {
        $missing = $this->missingAdvanceToDecisionRequirements($case);
        if ($missing === []) {
            return;
        }

        throw ValidationException::withMessages([
            'diligenceAdvance' => 'No puede avanzar a decisión. Falta: '.implode('; ', $missing).'.',
        ]);
    }

    public function advanceNoteFor(DisciplinaryCase $case): string
    {
        return $case->current_status === CaseStatus::COMITE_DISCIPLINARIO
            ? 'Avance a comunicado de decisión tras acta de comité disciplinario.'
            : 'Avance a comunicado de decisión tras la diligencia disciplinaria.';
    }
}
