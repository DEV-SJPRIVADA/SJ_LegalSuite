<?php

namespace Database\Seeders;

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
            'disciplinary.download-pdf',
        ]);

        /** Planeación (dirección): vista completa + fechas; responde en hilo de agenda Etapa A. */
        $planeacion = Role::firstOrCreate(['name' => 'planeacion', 'guard_name' => 'web']);
        $planeacion->syncPermissions([
            'disciplinary.view',
            'disciplinary.assign-date',
            'employees.view',
            'disciplinary.download-pdf',
        ]);

        /** Área administrativa: apertura de informes disciplinarios y evidencias (similar a operaciones). */
        $administrativa = Role::firstOrCreate(['name' => 'administrativa', 'guard_name' => 'web']);
        $administrativa->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'employees.view',
            'employees.manage',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions([
            'disciplinary.view',
            'disciplinary.view-dashboard',
            'disciplinary.export',
            'employees.view',
            'users.view',
            'disciplinary.download-pdf',
        ]);

        /** Operaciones (dirección / central): gestión completa del frente operativo. */
        $admin_op = Role::firstOrCreate(['name' => 'operaciones', 'guard_name' => 'web']);
        $admin_op->syncPermissions([
            'disciplinary.view',
            'disciplinary.create',
            'disciplinary.upload-document',
            'disciplinary.generate-inform',
            'disciplinary.review-inform',
            'disciplinary.review-inform-all',
            'employees.view',
            'employees.manage',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        /** Supervisor de campo: pool por turno — informe FO-GJ-51 y evidencias en expedientes ya formalizados. */
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'disciplinary.generate-inform',
            'disciplinary.upload-document',
            'employees.view',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        /** Operador / central de campo: mismo alcance que supervisor (pool por turno). */
        $operador = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $operador->syncPermissions([
            'disciplinary.generate-inform',
            'disciplinary.upload-document',
            'employees.view',
            'disciplinary.upload-notification',
            'disciplinary.download-pdf',
        ]);

        /** Programador: expedientes formalizados — programar fechas en etapas (sin hilo de agenda). */
        $programador = Role::firstOrCreate(['name' => 'programador', 'guard_name' => 'web']);
        $programador->syncPermissions([
            'disciplinary.assign-date',
            'disciplinary.download-pdf',
        ]);

        foreach (Role::where('guard_name', 'web')->whereIn('name', ['juridico', 'gerencia'])->get() as $legacy) {
            $legacy->delete();
        }
    }
}
