<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

            'personnel.view',
            'personnel.manage',

            'users.view',
            'users.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        /** Abogado operativo: sólo gestiona casos donde figure como asignado (ownership en política). */
        $abogado = Role::firstOrCreate(['name' => 'abogado', 'guard_name' => 'web']);
        $abogado->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.update',
            'disciplinary.transition',
            'disciplinary.upload-document',
        ]);

        /** Planeación: visualización + programación de fechas en etapas (sin mover estados). */
        $planeacion = Role::firstOrCreate(['name' => 'planeacion', 'guard_name' => 'web']);
        $planeacion->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.assign-date',
            'personnel.view',
        ]);

        /** Área administrativa: apertura de informes disciplinarios y evidencias (similar a operaciones). */
        $administrativa = Role::firstOrCreate(['name' => 'administrativa', 'guard_name' => 'web']);
        $administrativa->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'personnel.view',
        ]);

        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.export',
            'personnel.view',
            'users.view',
        ]);

        $admin_op = Role::firstOrCreate(['name' => 'operaciones', 'guard_name' => 'web']);
        $admin_op->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'personnel.view',
        ]);

        foreach (Role::where('guard_name', 'web')->whereIn('name', ['juridico', 'gerencia'])->get() as $legacy) {
            $legacy->delete();
        }
    }
}
