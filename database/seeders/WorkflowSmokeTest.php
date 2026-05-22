<?php

namespace Database\Seeders;

use App\Models\Disciplinary\Fault;
use App\Models\Employee;
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
        $employee = Employee::firstOrCreate(
            ['document_number' => '900000001'],
            [
                'first_name' => 'Trabajador',
                'last_name' => 'Prueba',
                'document_type' => 'CC',
                'job_title' => 'Guarda de seguridad',
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
