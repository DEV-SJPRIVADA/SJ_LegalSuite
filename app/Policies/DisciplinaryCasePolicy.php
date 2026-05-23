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
 * - abogado → casos asignados + bandeja INFORME sin titular (`claim`); puede ver PDF FO-GJ-51 del expediente si existe (`viewFo51InformePdf`).
 * - supervisor / operador → pool por turno (casos fuera de borrador); informe FO-GJ-51 + evidencias.
 * - programador → expedientes ya formalizados (no borrador); programar fechas de etapa.
 * - planeacion → listado «informes»: casos en citación o reprogramación con mensaje inicial del titular en el hilo FO-GJ-03 ↔ planeación;
 *   sin dashboard ni formatos; al salir de esos estados el caso deja de mostrarse allí (según alcance dinámico).
 * - administrativa / operaciones → informes + evidencias.
 * - Asignar / reasignar abogado titular: `admin` o permiso `disciplinary.assign` (no solo lectura), vía `assign`.
 * - Hilo agenda (citación / reprogramación): `postAgendaLawyer` (abogado titular), `postAgendaPlanning` (planeación o admin sin atajo `before`).
 */
class DisciplinaryCasePolicy
{
    /** @var list<string> */
    private const READ_ABILITIES = ['viewAny', 'view', 'viewDashboard'];

    /**
     * El admin no debe suplantar al abogado titular ni a planeación en el hilo de agenda;
     * se evalúa en los métodos específicos.
     *
     * @var list<string>
     */
    private const ADMIN_DO_NOT_SHORT_CIRCUIT = ['postAgendaLawyer', 'postAgendaPlanning', 'assign', 'claim'];

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->hasRole('admin')) {
            return null;
        }

        if ($user->read_only) {
            return in_array($ability, self::READ_ABILITIES, true) ? true : false;
        }

        if (in_array($ability, self::ADMIN_DO_NOT_SHORT_CIRCUIT, true)) {
            return null;
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
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        if ($user->hasRole('programador')) {
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        if ($user->hasRole('abogado')) {
            return (int) $case->assigned_lawyer_id === (int) $user->id
                || $case->isInInformePool();
        }

        if ($user->hasRole('planeacion')) {
            return $case->isVisibleToPlaneacionUser();
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
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        return $user->can('view', $case);
    }

    public function assign(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasPermissionTo('disciplinary.assign');
    }

    /** Tomar gestión de un expediente en bandeja INFORME (sin titular). */
    public function claim(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasRole('abogado')) {
            return false;
        }

        return $case->isInInformePool();
    }

    public function assignPlanner(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.assign-planner');
    }

    /** Coordinación FO-GJ-03 / fechas: mensajes del titular desde citación o reprogramación. */
    public function postAgendaLawyer(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $case->allowsAgendaThread()) {
            return false;
        }

        return (int) $case->assigned_lawyer_id === (int) $user->id;
    }

    /** Respuestas y adjuntos del lado planeación (rol planeación; admin evaluado en el método). */
    public function postAgendaPlanning(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $case->allowsAgendaThread()) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('planeacion');
    }

    /** Plantilla FO-GJ-51 (PDF): operaciones crean casos; campo sólo con permiso dedicado. */
    public function generateFo51Inform(User $user): bool
    {
        return $user->hasPermissionTo('disciplinary.generate-inform');
    }

    /**
     * Ver el bloque FO-GJ-51 en expediente: quien puede generar informe, o el abogado titular
     * cuando ya existe PDF del informe en el caso (solo consulta).
     */
    public function viewFo51InformePdf(User $user, DisciplinaryCase $case): bool
    {
        if ($this->generateFo51Inform($user)) {
            return true;
        }

        if (! $user->hasRole('abogado') || (int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        $doc = $case->primaryFo51InformeDocument();

        return $doc !== null && $doc->path !== '';
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
            return $case->isVisibleToDisciplinaryFieldPool();
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
        if ($user->hasRole('planeacion')) {
            return false;
        }

        return $user->hasAnyRole(['auditor', 'abogado'])
            || $user->hasPermissionTo('disciplinary.view-dashboard');
    }

    /** Catálogo de formatos FO-GJ (referencia para quien puede consultar expedientes). */
    public function viewOfficialForms(User $user): bool
    {
        if ($user->isMinimalDisciplinaryPortalUser() || $user->hasRole('planeacion')) {
            return false;
        }

        return $this->viewAny($user);
    }
}
