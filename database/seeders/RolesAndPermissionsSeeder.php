<?php

namespace Database\Seeders;

use App\Enums\PlatformLevel;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.create',
            'disciplinary.update',
            'disciplinary.delete',
            'disciplinary.transition',
            'disciplinary.assign',
            'disciplinary.assign-date',
            'disciplinary.upload-document',
            'disciplinary.export',
            'disciplinary.generate-inform',
            'disciplinary.review-inform',
            'disciplinary.review-inform-all',
            'disciplinary.assign-planner',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',

            'employees.view',
            'employees.manage',

            'users.view',
            'users.manage',

            'settings.manage-territory',
            'settings.manage-citation-articles',
            'settings.manage-diligence-questions',
            'settings.manage-supervision-zones',

            'licitaciones.view',
            'licitaciones.view-dashboard',
            'licitaciones.create',
            'licitaciones.update',
            'licitaciones.delete',
            'licitaciones.manage-solicitudes',
            'licitaciones.upload-document',

            'module.disciplinary',
            'module.licitaciones',
            'module.employees',
            'module.users',
            'module.settings',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $nivel1 = $this->upsertLevel(PlatformLevel::Nivel1);
        $nivel1->syncPermissions(Permission::all());

        $nivel6 = $this->upsertLevel(PlatformLevel::Nivel6);
        $nivel6->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.update',
            'disciplinary.transition',
            'disciplinary.upload-document',
            'disciplinary.download-pdf',
            'licitaciones.view',
            'licitaciones.view-dashboard',
            'licitaciones.create',
            'licitaciones.update',
            'licitaciones.delete',
            'licitaciones.manage-solicitudes',
            'licitaciones.upload-document',
        ]);

        $nivel3 = $this->upsertLevel(PlatformLevel::Nivel3);
        $nivel3->syncPermissions([
            'disciplinary.view',
            'disciplinary.assign-date',
            'employees.view',
            'disciplinary.download-pdf',
        ]);

        $nivel4 = $this->upsertLevel(PlatformLevel::Nivel4);
        $nivel4->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'employees.view',
            'employees.manage',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        $nivel5 = $this->upsertLevel(PlatformLevel::Nivel5);
        $nivel5->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.export',
            'employees.view',
            'users.view',
            'disciplinary.download-pdf',
            'licitaciones.view',
            'licitaciones.view-dashboard',
        ]);

        $nivel2 = $this->upsertLevel(PlatformLevel::Nivel2);
        $nivel2->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'disciplinary.generate-inform',
            'disciplinary.review-inform',
            'employees.view',
            'employees.manage',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
            'licitaciones.view',
            'licitaciones.upload-document',
        ]);

        $nivel7 = $this->upsertLevel(PlatformLevel::Nivel7);
        $nivel7->syncPermissions([
            'disciplinary.generate-inform',
            'disciplinary.upload-document',
            'employees.view',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        $nivel8 = $this->upsertLevel(PlatformLevel::Nivel8);
        $nivel8->syncPermissions([
            'disciplinary.generate-inform',
            'disciplinary.upload-document',
            'employees.view',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        $nivel9 = $this->upsertLevel(PlatformLevel::Nivel9);
        $nivel9->syncPermissions([
            'disciplinary.assign-date',
            'disciplinary.download-pdf',
        ]);

        foreach (array_keys(PlatformLevel::legacyMap()) as $legacy) {
            Role::where('guard_name', 'web')->where('name', $legacy)->delete();
        }

        $this->backfillUserModuleAccess();
    }

    /**
     * Asigna module.* según permisos ya concedidos, para usuarios que aún no tienen módulos explícitos.
     */
    private function backfillUserModuleAccess(): void
    {
        $users = \App\Models\User::query()->with('permissions', 'roles.permissions')->get();

        foreach ($users as $user) {
            if ($user->hasPlatformLevel(
                PlatformLevel::Nivel1,
                PlatformLevel::Nivel5,
                PlatformLevel::Nivel6,
            )) {
                continue;
            }

            $alreadyHasModule = $user->getDirectPermissions()
                ->contains(fn ($p) => str_starts_with($p->name, 'module.'));

            if ($alreadyHasModule) {
                continue;
            }

            $grants = [];

            if ($user->can('viewDashboard', \App\Models\Disciplinary\DisciplinaryCase::class)
                || $user->can('viewAny', \App\Models\Disciplinary\DisciplinaryCase::class)
                || $user->hasPlatformLevel(PlatformLevel::Nivel3, PlatformLevel::Nivel7)) {
                $grants[] = 'module.disciplinary';
            }

            if ($user->can('viewDashboard', \App\Models\Licitaciones\Licitacion::class)
                || $user->can('viewAny', \App\Models\Licitaciones\Licitacion::class)
                || $user->can('viewAny', \App\Models\Licitaciones\LicitacionSolicitud::class)) {
                $grants[] = 'module.licitaciones';
            }

            if ($user->can('viewAny', \App\Models\Employee::class)) {
                $grants[] = 'module.employees';
            }

            if ($user->can('viewAny', \App\Models\User::class)) {
                $grants[] = 'module.users';
            }

            if ($user->can('settings.manage-territory')
                || $user->can('settings.manage-citation-articles')
                || $user->can('settings.manage-diligence-questions')
                || $user->can('settings.manage-supervision-zones')) {
                $grants[] = 'module.settings';
            }

            if ($grants !== []) {
                $user->givePermissionTo($grants);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function upsertLevel(PlatformLevel $level): Role
    {
        $role = Role::firstOrCreate(
            ['name' => $level->value, 'guard_name' => 'web'],
            ['level_number' => $level->number(), 'subtitle' => $level->subtitle()]
        );

        $role->update([
            'level_number' => $level->number(),
            'subtitle' => $level->subtitle(),
        ]);

        return $role;
    }
}
