<?php

namespace App\Policies;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;

/**
 * Autorización del módulo disciplinario:
 *
 * - admin (sin modo solo lectura) → control total vía `before`.
 * - admin en modo solo lectura → sólo consulta (listados, detalle, dashboard).
 * - Otros usuarios con `read_only` → igual: consulta sin mutaciones.
 * - abogado → sólo casos asignados (si no está en solo lectura).
 * - supervisor / operador → sólo casos con `assigned_operator_id`; informe FO-GJ-51 + evidencias.
 * - programador → sólo casos con `assigned_planner_id`; programar fechas de etapa.
 * - planeacion → fechas en etapas (assign-date) y vista completa.
 * - administrativa / operaciones → informes + evidencias y gestión operativa.
 */
class DisciplinaryCasePolicy
{
    /** @var list<string> */
    private const READ_ABILITIES = ['viewAny', 'view', 'viewDashboard'];

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->hasRole('admin')) {
            return null;
        }

        if ($user->read_only) {
            return in_array($ability, self::READ_ABILITIES, true) ? true : false;
        }

        return true;
    }

    private function deniesMutation(User $user): bool
    {
        return (bool) $user->read_only;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'auditor', 'abogado', 'planeacion', 'administrativa', 'operaciones',
            'supervisor', 'operador', 'programador',
        ])
            || $user->hasPermissionTo('disciplinary.view');
    }

    public function view(User $user, DisciplinaryCase $case): bool
    {
        if ($user->hasRole('auditor')) {
            return true;
        }

        if ($user->hasAnyRole(['supervisor', 'operador'])) {
            return $case->assigned_operator_id === $user->id;
        }

        if ($user->hasRole('programador')) {
            return $case->assigned_planner_id === $user->id;
        }

        if ($user->hasRole('abogado')) {
            return $case->assigned_lawyer_id === $user->id;
        }

        if ($user->hasPermissionTo('disciplinary.view')) {
            return true;
        }

        return $case->reporter_id === $user->id || $case->assigned_lawyer_id === $user->id;
    }

    public function create(User $user): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ($user->hasAnyRole(['supervisor', 'operador', 'programador'])) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.create');
    }

    public function update(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ($user->hasAnyRole(['supervisor', 'operador', 'programador'])) {
            return false;
        }

        if ($user->hasRole('abogado')) {
            return $case->assigned_lawyer_id === $user->id;
        }

        return $user->hasPermissionTo('disciplinary.update')
            && ($case->assigned_lawyer_id === $user->id || $case->reporter_id === $user->id);
    }

    public function transition(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ($user->hasAnyRole(['supervisor', 'operador', 'programador'])) {
            return false;
        }

        if ($user->hasRole('abogado')) {
            return $case->assigned_lawyer_id === $user->id;
        }

        if (! $user->hasPermissionTo('disciplinary.transition')) {
            return false;
        }

        return $case->assigned_lawyer_id === $user->id;
    }

    public function assignDate(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPermissionTo('disciplinary.assign-date')) {
            return false;
        }

        if ($user->hasRole('programador')) {
            return $case->assigned_planner_id === $user->id;
        }

        return $user->can('view', $case);
    }

    public function assign(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.assign');
    }

    public function assignFieldOperator(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.assign-field-operator');
    }

    public function assignPlanner(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.assign-planner');
    }

    /** Plantilla FO-GJ-51 (PDF): operaciones crean casos; campo sólo con permiso dedicado. */
    public function generateFo51Inform(User $user): bool
    {
        return $user->hasPermissionTo('disciplinary.generate-inform');
    }

    public function uploadDocument(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPermissionTo('disciplinary.upload-document')) {
            return false;
        }

        if ($user->hasRole('abogado')) {
            return $case->assigned_lawyer_id === $user->id;
        }

        if ($user->hasAnyRole(['supervisor', 'operador'])) {
            return $case->assigned_operator_id === $user->id;
        }

        return true;
    }

    public function delete(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.delete')
            && ! $case->isFinalized();
    }

    public function viewDashboard(User $user): bool
    {
        return $user->hasAnyRole(['auditor', 'abogado', 'planeacion'])
            || $user->hasPermissionTo('disciplinary.view-dashboard');
    }

    /** Catálogo de formatos FO-GJ (referencia para quien puede consultar expedientes). */
    public function viewOfficialForms(User $user): bool
    {
        if ($user->isMinimalDisciplinaryPortalUser()) {
            return false;
        }

        return $this->viewAny($user);
    }
}
