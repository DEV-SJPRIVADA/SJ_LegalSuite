<?php

namespace App\Support\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alcance territorial y por cargo para perfiles de campo (supervisor / operador).
 */
final class FieldDisciplinaryScopeService
{
    public function requiresTerritorialScope(User $user): bool
    {
        if ($user->hasRole('nivel1')) {
            return false;
        }

        return $user->hasAnyRole(['nivel7', 'nivel8']);
    }

    public function hasConfiguredScope(User $user): bool
    {
        if (! $this->requiresTerritorialScope($user)) {
            return true;
        }

        return $this->authorizedMunicipalityCodes($user) !== [];
    }

    /**
     * @return list<string>
     */
    public function authorizedMunicipalityCodes(User $user): array
    {
        $user->loadMissing('authorizedMunicipalities:municipality_code');

        return $user->authorizedMunicipalities
            ->pluck('municipality_code')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function employeeInScope(User $user, Employee $employee): bool
    {
        if (! $this->requiresTerritorialScope($user)) {
            return true;
        }

        if (! $this->hasConfiguredScope($user)) {
            return false;
        }

        if (! $employee->isGuardaCargo()) {
            return false;
        }

        $code = (string) ($employee->municipality_code ?? '');

        return $code !== '' && in_array($code, $this->authorizedMunicipalityCodes($user), true);
    }

    public function caseEmployeeInScope(User $user, DisciplinaryCase $case): bool
    {
        $case->loadMissing('employee.employeeJobPosition');

        if (! $case->employee instanceof Employee) {
            return false;
        }

        return $this->employeeInScope($user, $case->employee);
    }

    /** @param Builder<Employee> $query */
    public function applyEmployeeScope(Builder $query, User $user): Builder
    {
        if (! $this->requiresTerritorialScope($user)) {
            return $query;
        }

        if (! $this->hasConfiguredScope($user)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('employeeJobPosition', fn (Builder $position) => $position->where('is_guarda', true))
            ->whereIn('municipality_code', $this->authorizedMunicipalityCodes($user));
    }

    /** @param Builder<User> $query */
    public function applySupervisorCandidatesForMunicipality(Builder $query, ?string $municipalityCode): Builder
    {
        if (! filled($municipalityCode)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->role('nivel7')
            ->active()
            ->whereHas('authorizedMunicipalities', fn (Builder $inner) => $inner
                ->where('user_authorized_municipalities.municipality_code', $municipalityCode));
    }

    public function assertEmployeeInScope(User $user, Employee $employee): void
    {
        if ($this->employeeInScope($user, $employee)) {
            return;
        }

        if (! $this->requiresTerritorialScope($user)) {
            return;
        }

        if (! $this->hasConfiguredScope($user)) {
            throw new \InvalidArgumentException('Su usuario no tiene ciudades autorizadas. Contacte al administrador.');
        }

        if (! $employee->isGuardaCargo()) {
            throw new \InvalidArgumentException('Solo puede gestionar disciplinarios de empleados con cargo de guarda.');
        }

        throw new \InvalidArgumentException('El empleado no pertenece a una ciudad autorizada para su perfil.');
    }

    public function assertSupervisorCoversCase(User $supervisor, DisciplinaryCase $case): void
    {
        $case->loadMissing('employee');
        $code = (string) ($case->employee?->municipality_code ?? '');

        if ($code === '') {
            throw new \InvalidArgumentException('El empleado del caso no tiene ciudad de labor registrada.');
        }

        if (! in_array($code, $this->authorizedMunicipalityCodes($supervisor), true)) {
            throw new \InvalidArgumentException('El supervisor seleccionado no está autorizado para la ciudad de labor del empleado.');
        }
    }
}
