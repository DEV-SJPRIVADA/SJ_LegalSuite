<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DiligenceAttendanceService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
    ) {}

    public function isRegistered(DisciplinaryCase $case): bool
    {
        return $case->diligence_attendance !== null;
    }

    public function attendance(DisciplinaryCase $case): ?DiligenceAttendance
    {
        $value = $case->diligence_attendance;

        if ($value instanceof DiligenceAttendance) {
            return $value;
        }

        return DiligenceAttendance::tryFrom((string) $value);
    }

    public function canRegister(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::DILIGENCIA
            && ! $this->isRegistered($case)
            && $case->citation_confirmed_date !== null;
    }

    public function register(DisciplinaryCase $case, User $actor, DiligenceAttendance $attendance): DisciplinaryCase
    {
        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'diligenceAttendance' => 'Solo el abogado titular puede registrar la asistencia.',
            ]);
        }

        if (! $this->canRegister($case)) {
            if ($this->isRegistered($case)) {
                throw ValidationException::withMessages([
                    'diligenceAttendance' => 'La asistencia ya fue registrada y no puede modificarse.',
                ]);
            }

            throw ValidationException::withMessages([
                'diligenceAttendance' => 'No es posible registrar la asistencia en el estado actual del expediente.',
            ]);
        }

        $case->forceFill([
            'diligence_attendance' => $attendance,
            'diligence_attendance_registered_at' => now(),
            'diligence_attendance_registered_by' => $actor->id,
        ])->save();

        $this->audit->logCase(
            $case->fresh(),
            $actor,
            ActionType::DILIGENCIA_ASISTENCIA_REGISTRADA,
            'Asistencia a diligencia registrada: '.$attendance->label().'.',
            ['diligence_attendance' => $attendance->value],
        );

        return $case->fresh();
    }
}
