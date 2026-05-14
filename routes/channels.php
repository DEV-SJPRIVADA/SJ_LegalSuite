<?php

use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('disciplinary.case.{caseId}', function ($user, string $caseId) {
    $case = DisciplinaryCase::query()->find($caseId);

    if ($case === null) {
        return false;
    }

    return Gate::forUser($user)->allows('view', $case);
});
