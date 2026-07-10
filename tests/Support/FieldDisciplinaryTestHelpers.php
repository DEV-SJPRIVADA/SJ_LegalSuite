<?php

namespace Tests\Support;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeScope;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Models\User;

trait FieldDisciplinaryTestHelpers
{
    protected function seedMunicipality(string $code = '76001', string $name = 'Cali'): void
    {
        ColombianMunicipality::query()->firstOrCreate(
            ['municipality_code' => $code],
            [
                'department_code' => substr($code, 0, 2),
                'department_name' => 'Departamento prueba',
                'municipality_name' => $name,
            ],
        );
    }

    protected function guardaJobPositionId(): int
    {
        return (int) EmployeeJobPosition::query()->where('is_guarda', true)->value('id');
    }

    protected function seedGuardaEmployee(string $documentNumber, string $municipalityCode = '76001'): Employee
    {
        $this->seedMunicipality($municipalityCode);

        $positionId = $this->guardaJobPositionId();
        $positionName = EmployeeJobPosition::query()->whereKey($positionId)->value('name') ?? 'GUARDA DE SEGURIDAD';

        return Employee::query()->create([
            'first_name' => 'Guarda',
            'last_name' => 'Prueba',
            'document_number' => $documentNumber,
            'document_type' => 'CC',
            'residence_municipality_code' => $municipalityCode,
            'residence_department_code' => substr($municipalityCode, 0, 2),
            'municipality_code' => $municipalityCode,
            'work_department_code' => substr($municipalityCode, 0, 2),
            'employee_job_position_id' => $positionId,
            'employee_scope' => EmployeeScope::Operativo->value,
            'job_title' => $positionName,
            'hired_at' => '2024-01-01',
            'contract_type' => EmployeeContractType::TerminoIndefinido->value,
            'is_active' => true,
        ]);
    }

    protected function seedFieldUserWithCities(string $role, array $municipalityCodes): User
    {
        $user = User::factory()->create([
            'email' => $role.'-scope-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $user->authorizedMunicipalities()->sync($municipalityCodes);

        return $user->fresh(['authorizedMunicipalities']);
    }
}
