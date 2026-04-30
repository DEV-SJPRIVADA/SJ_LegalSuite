<?php

namespace App\Policies;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;

/**
 * Reglas de autorización del caso disciplinario.
 *
 * Roles:
 *   - admin       → todo
 *   - juridico    → control total sobre casos
 *   - gerencia    → ver, supervisar; sin transición de estados
 *   - auditor     → sólo lectura
 *
 * Áreas con permisos específicos:
 *   - operaciones / administrativa → pueden crear casos y subir evidencias
 *   - planeacion → puede agendar fechas
 */
class DisciplinaryCasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['juridico', 'gerencia', 'auditor'])
            || $user->hasPermissionTo('disciplinary.view');
    }

    public function view(User $user, DisciplinaryCase $case): bool
    {
        if ($user->hasAnyRole(['juridico', 'gerencia', 'auditor'])) {
            return true;
        }

        if ($user->hasPermissionTo('disciplinary.view')) {
            return true;
        }

        return $case->reporter_id === $user->id || $case->assigned_lawyer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['juridico'])
            || $user->hasPermissionTo('disciplinary.create');
    }

    public function update(User $user, DisciplinaryCase $case): bool
    {
        if ($user->hasRole('juridico')) {
            return true;
        }

        return $user->hasPermissionTo('disciplinary.update')
            && ($case->assigned_lawyer_id === $user->id || $case->reporter_id === $user->id);
    }

    public function transition(User $user, DisciplinaryCase $case): bool
    {
        if ($user->hasRole('juridico')) {
            return true;
        }

        if (! $user->hasPermissionTo('disciplinary.transition')) {
            return false;
        }

        return $case->assigned_lawyer_id === $user->id;
    }

    public function assign(User $user, DisciplinaryCase $case): bool
    {
        return $user->hasRole('juridico')
            || $user->hasPermissionTo('disciplinary.assign');
    }

    public function uploadDocument(User $user, DisciplinaryCase $case): bool
    {
        if ($user->hasRole('juridico')) {
            return true;
        }

        return $user->hasPermissionTo('disciplinary.upload-document');
    }

    public function delete(User $user, DisciplinaryCase $case): bool
    {
        return $user->hasRole('juridico')
            && ! $case->isFinalized();
    }

    public function viewDashboard(User $user): bool
    {
        return $user->hasAnyRole(['juridico', 'gerencia', 'auditor'])
            || $user->hasPermissionTo('disciplinary.view-dashboard');
    }
}
