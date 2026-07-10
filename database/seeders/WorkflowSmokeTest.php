<?php

namespace Database\Seeders;

use App\Models\Disciplinary\Fault;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos mínimos para probar flujo disciplinario en local.
 *
 *  1. Crear employee y faltas
 *  2. (manual) crear caso / informe según UI
 */
class WorkflowSmokeTest extends Seeder
{
    public function run(): void
    {
        $guardaId = EmployeeJobPosition::query()->where('slug', 'guarda-seguridad')->value('id');

        $municipalityCode = ColombianMunicipality::query()->value('municipality_code');
        $departmentCode = $municipalityCode ? substr((string) $municipalityCode, 0, 2) : null;

        $employee = Employee::firstOrCreate(
            ['document_number' => '900000001'],
            [
                'first_name' => 'Trabajador',
                'last_name' => 'Prueba',
                'document_type' => 'CC',
                'residence_municipality_code' => $municipalityCode,
                'residence_department_code' => $departmentCode,
                'municipality_code' => $municipalityCode,
                'work_department_code' => $departmentCode,
                'employee_job_position_id' => $guardaId,
                'employee_scope' => 'operativo',
                'job_title' => 'Guarda de seguridad',
                'hired_at' => now()->toDateString(),
                'contract_type' => 'termino_indefinido',
                'is_active' => true,
            ]
        );

        Fault::firstOrCreate(
            ['code' => 'F006'],
            ['name' => 'Falta genérica (prueba)', 'is_active' => true]
        );

        $lawyer = User::where('email', 'abogado@sjlegalsuite.local')->first();
        if ($lawyer) {
            // Reservado para pruebas manuales con usuario abogado existente.
        }
    }
}
