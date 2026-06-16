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
        $missing = [];

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            $missing[] = 'El expediente debe estar en etapa de diligencia';

            return $missing;
        }

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
}
