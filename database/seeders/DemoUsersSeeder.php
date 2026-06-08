<?php

namespace Database\Seeders;

use App\Enums\UserArea;
use App\Models\JobPosition;
use App\Models\OrganizationalArea;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea un usuario por cada rol/área para poder probar el sistema localmente.
 * Las contraseñas son las mismas en local — cambiarlas antes de cualquier deploy.
 *
 * Contraseña en texto plano: el modelo aplica cast `hashed` al persistir.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['email' => 'admin@sjlegalsuite.local', 'name' => 'Administrador', 'role' => 'admin', 'area' => UserArea::JURIDICA, 'read_only' => false],
            ['email' => 'admin.consulta@sjlegalsuite.local', 'name' => 'Admin solo lectura', 'role' => 'admin', 'area' => UserArea::GERENCIA, 'read_only' => true],
            ['email' => 'abogado@sjlegalsuite.local', 'name' => 'Abogado asignado', 'role' => 'abogado', 'area' => UserArea::JURIDICA, 'read_only' => false],
            ['email' => 'planeacion@sjlegalsuite.local', 'name' => 'Planeación', 'role' => 'planeacion', 'area' => UserArea::PLANEACION, 'read_only' => false],
            ['email' => 'administrativa@sjlegalsuite.local', 'name' => 'Área administrativa', 'role' => 'administrativa', 'area' => UserArea::ADMINISTRATIVA, 'read_only' => false],
            ['email' => 'auditor@sjlegalsuite.local', 'name' => 'Auditor', 'role' => 'auditor', 'area' => UserArea::GERENCIA, 'read_only' => false],
            ['email' => 'operaciones@sjlegalsuite.local', 'name' => 'Operaciones', 'role' => 'operaciones', 'area' => UserArea::OPERACIONES, 'read_only' => false],
            ['email' => 'supervisor@sjlegalsuite.local', 'name' => 'Supervisor campo', 'role' => 'supervisor', 'area' => UserArea::OPERACIONES, 'read_only' => false],
            ['email' => 'operador@sjlegalsuite.local', 'name' => 'Operador central', 'role' => 'operador', 'area' => UserArea::OPERACIONES, 'read_only' => false],
            ['email' => 'programador@sjlegalsuite.local', 'name' => 'Programador fechas', 'role' => 'programador', 'area' => UserArea::PLANEACION, 'read_only' => false],
        ];

        foreach ($defaults as $row) {
            $areaId = OrganizationalArea::where('slug', $row['area']->value)->value('id');

            $jobPositionId = null;
            if ($row['role'] !== 'admin' && $areaId !== null) {
                $jobPositionId = JobPosition::query()
                    ->where('organizational_area_id', $areaId)
                    ->where('permission_role_name', $row['role'])
                    ->value('id');
            }

            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => 'SJseguridad2026',
                    'organizational_area_id' => $row['role'] === 'admin' ? null : $areaId,
                    'job_position_id' => $jobPositionId,
                    'area' => $row['area']->value,
                    'is_active' => true,
                    'read_only' => $row['read_only'],
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles([$row['role']]);

            if ($row['email'] === 'operaciones@sjlegalsuite.local') {
                $user->givePermissionTo('disciplinary.review-inform-all');
            }
        }
    }
}
