<?php

namespace App\Policies;

use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class InformeSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->hasReviewInformPermission($user);
    }

    public function view(User $user, InformeSubmission $informeSubmission): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $informeSubmission->submitted_by === $user->id
            && $informeSubmission->status === InformeSubmissionStatus::PENDIENTE_REVISION;
    }

    /** Enviar informe a cola de revisión (FO-GJ-51). */
    public function submit(User $user): bool
    {
        return $user->can('create', DisciplinaryCase::class)
            || $user->can('generateFo51Inform', DisciplinaryCase::class);
    }

    public function review(User $user, InformeSubmission $informeSubmission): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->hasReviewInformPermission($user)
            && $informeSubmission->status === InformeSubmissionStatus::PENDIENTE_REVISION;
    }

    private function hasReviewInformPermission(User $user): bool
    {
        try {
            return $user->hasPermissionTo('disciplinary.review-inform');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
