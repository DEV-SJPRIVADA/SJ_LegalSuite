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
            'disciplinary.generate-inform',
            'disciplinary.review-inform',
            'disciplinary.assign-field-operator',
            'disciplinary.assign-planner',

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

        /** Planeación (dirección): vista completa + delegación a programadores + fechas. */
        $planeacion = Role::firstOrCreate(['name' => 'planeacion', 'guard_name' => 'web']);
        $planeacion->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.assign-date',
            'disciplinary.assign-planner',
            'personnel.view',
        ]);

        /** Área administrativa: apertura de informes disciplinarios y evidencias (similar a operaciones). */
        $administrativa = Role::firstOrCreate(['name' => 'administrativa', 'guard_name' => 'web']);
        $administrativa->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'disciplinary.review-inform',
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

        /** Operaciones (dirección / central): gestión completa del frente operativo y asignación a campo. */
        $admin_op = Role::firstOrCreate(['name' => 'operaciones', 'guard_name' => 'web']);
        $admin_op->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'disciplinary.assign-field-operator',
            'disciplinary.generate-inform',
            'disciplinary.review-inform',
            'personnel.view',
        ]);

        /** Supervisor de campo: sólo casos asignados por operaciones — informe FO-GJ-51 y evidencias. */
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'disciplinary.generate-inform',
            'disciplinary.upload-document',
            'personnel.view',
        ]);

        /** Operador / central de campo: mismo alcance que supervisor. */
        $operador = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $operador->syncPermissions([
            'disciplinary.generate-inform',
            'disciplinary.upload-document',
            'personnel.view',
        ]);

        /** Programador: sólo solicitudes delegadas por planeación — programar fechas en etapas. */
        $programador = Role::firstOrCreate(['name' => 'programador', 'guard_name' => 'web']);
        $programador->syncPermissions([
            'disciplinary.assign-date',
        ]);

        foreach (Role::where('guard_name', 'web')->whereIn('name', ['juridico', 'gerencia'])->get() as $legacy) {
            $legacy->delete();
        }
    }
}
