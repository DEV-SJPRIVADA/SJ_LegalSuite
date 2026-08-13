<?php

namespace Database\Seeders;

use App\Enums\PlatformLevel;
use App\Enums\UserArea;
use App\Models\ColombianMunicipality;
use App\Models\Disciplinary\SupervisionZone;
use App\Models\JobPosition;
use App\Models\OrganizationalArea;
use App\Models\User;
use App\Services\Disciplinary\SupervisionZoneService;
use Illuminate\Database\Seeder;

/**
 * Crea un usuario por cada nivel/área para poder probar el sistema localmente.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSupervisionZone = SupervisionZone::query()->firstOrCreate(
            ['name' => 'Zona de supervisión demo'],
            ['code' => 'DEMO', 'is_active' => true],
        );

        $defaults = [
            ['email' => 'admin@sjlegalsuite.local', 'name' => 'Administrador', 'level' => PlatformLevel::Nivel1, 'area' => UserArea::JURIDICA, 'read_only' => false],
            ['email' => 'admin.consulta@sjlegalsuite.local', 'name' => 'Admin solo lectura', 'level' => PlatformLevel::Nivel1, 'area' => UserArea::GERENCIA, 'read_only' => true],
            ['email' => 'abogado@sjlegalsuite.local', 'name' => 'Abogado asignado', 'level' => PlatformLevel::Nivel6, 'area' => UserArea::JURIDICA, 'read_only' => false],
            ['email' => 'planeacion@sjlegalsuite.local', 'name' => 'Planeación', 'level' => PlatformLevel::Nivel3, 'area' => UserArea::PLANEACION, 'read_only' => false],
            ['email' => 'administrativa@sjlegalsuite.local', 'name' => 'Área administrativa', 'level' => PlatformLevel::Nivel4, 'area' => UserArea::ADMINISTRATIVA, 'read_only' => false],
            ['email' => 'auditor@sjlegalsuite.local', 'name' => 'Auditor', 'level' => PlatformLevel::Nivel5, 'area' => UserArea::GERENCIA, 'read_only' => false],
            ['email' => 'operaciones@sjlegalsuite.local', 'name' => 'Operaciones', 'level' => PlatformLevel::Nivel2, 'area' => UserArea::OPERACIONES, 'read_only' => false],
            ['email' => 'supervisor@sjlegalsuite.local', 'name' => 'Supervisor campo', 'level' => PlatformLevel::Nivel7, 'area' => UserArea::OPERACIONES, 'read_only' => false],
            ['email' => 'operador@sjlegalsuite.local', 'name' => 'Operador central', 'level' => PlatformLevel::Nivel8, 'area' => UserArea::OPERACIONES, 'read_only' => false],
            ['email' => 'programador@sjlegalsuite.local', 'name' => 'Programador fechas', 'level' => PlatformLevel::Nivel9, 'area' => UserArea::PLANEACION, 'read_only' => false],
        ];

        foreach ($defaults as $row) {
            $areaId = OrganizationalArea::where('slug', $row['area']->value)->value('id');

            $jobPositionId = null;
            if ($row['level'] !== PlatformLevel::Nivel1 && $areaId !== null) {
                $jobPositionId = JobPosition::query()
                    ->where('organizational_area_id', $areaId)
                    ->where('permission_level_name', $row['level']->value)
                    ->value('id');
            }

            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => 'SJseguridad2026',
                    'organizational_area_id' => $row['level'] === PlatformLevel::Nivel1 ? null : $areaId,
                    'job_position_id' => $jobPositionId,
                    'area' => $row['area']->value,
                    'is_active' => true,
                    'read_only' => $row['read_only'],
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles([$row['level']->value]);

            if ($row['level'] === PlatformLevel::Nivel7) {
                app(SupervisionZoneService::class)->assignUser($user, $defaultSupervisionZone);
            }

            if ($row['email'] === 'operaciones@sjlegalsuite.local') {
                $user->givePermissionTo('disciplinary.review-inform-all');
            }

            if ($row['level']->requiresAuthorizedCities()) {
                $defaultMunicipality = ColombianMunicipality::query()->orderBy('municipality_code')->value('municipality_code');
                if ($defaultMunicipality) {
                    $user->authorizedMunicipalities()->sync([$defaultMunicipality]);
                }
            }
        }
    }
}
