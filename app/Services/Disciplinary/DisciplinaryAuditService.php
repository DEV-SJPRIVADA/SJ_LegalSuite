<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\User;

class DisciplinaryAuditService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function logCase(
        DisciplinaryCase $case,
        User $actor,
        ActionType $type,
        ?string $description = null,
        ?array $metadata = null,
        ?DisciplinaryStage $stage = null,
        ?CaseStatus $from = null,
        ?CaseStatus $to = null,
    ): DisciplinaryAction {
        return DisciplinaryAction::create([
            'disciplinary_case_id' => $case->id,
            'disciplinary_stage_id' => $stage?->id,
            'user_id' => $actor->id,
            'action_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'description' => $description,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function logInforme(
        InformeSubmission $submission,
        User $actor,
        ActionType $type,
        ?string $description = null,
        ?array $metadata = null,
    ): DisciplinaryAction {
        return DisciplinaryAction::create([
            'disciplinary_case_id' => $submission->disciplinary_case_id,
            'informe_submission_id' => $submission->id,
            'user_id' => $actor->id,
            'action_type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }
}
