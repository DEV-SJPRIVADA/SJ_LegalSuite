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

        $juridico = Role::firstOrCreate(['name' => 'juridico', 'guard_name' => 'web']);
        $juridico->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.create',
            'disciplinary.update',
            'disciplinary.transition',
            'disciplinary.assign',
            'disciplinary.upload-document',
            'disciplinary.export',
            'personnel.view',
            'personnel.manage',
            'users.view',
        ]);

        $gerencia = Role::firstOrCreate(['name' => 'gerencia', 'guard_name' => 'web']);
        $gerencia->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.export',
            'personnel.view',
            'users.view',
        ]);

        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
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
    }
}
