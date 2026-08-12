<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $name = 'settings.manage-diligence-questions';

        $exists = DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => $name,
                'guard_name' => $guard,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionId = DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->value('id');

        $adminRoleId = DB::table('roles')
            ->where('name', 'nivel1')
            ->where('guard_name', $guard)
            ->value('id');

        if ($permissionId && $adminRoleId) {
            $linked = DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', $adminRoleId)
                ->exists();

            if (! $linked) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRoleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'settings.manage-diligence-questions')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
