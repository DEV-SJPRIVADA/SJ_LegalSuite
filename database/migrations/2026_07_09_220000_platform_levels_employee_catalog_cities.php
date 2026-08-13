<?php

use App\Enums\EmployeeScope;
use App\Enums\PlatformLevel;
use App\Models\EmployeeJobPosition;
use Database\Seeders\EmployeeJobPositionsCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('level_number')->nullable()->after('guard_name');
            $table->string('subtitle', 120)->nullable()->after('level_number');
        });

        foreach (PlatformLevel::legacyMap() as $legacy => $level) {
            $platformLevel = PlatformLevel::from($level);

            DB::table('roles')
                ->where('guard_name', 'web')
                ->where('name', $legacy)
                ->update([
                    'name' => $level,
                    'level_number' => $platformLevel->number(),
                    'subtitle' => $platformLevel->subtitle(),
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasColumn('job_positions', 'permission_role_name')) {
            Schema::table('job_positions', function (Blueprint $table) {
                $table->renameColumn('permission_role_name', 'permission_level_name');
            });
        }

        foreach (PlatformLevel::legacyMap() as $legacy => $level) {
            DB::table('job_positions')
                ->where('permission_level_name', $legacy)
                ->update(['permission_level_name' => $level]);
        }

        Schema::table('employee_job_positions', function (Blueprint $table) {
            $table->string('employee_scope', 20)->default(EmployeeScope::Administrativo->value)->after('is_guarda');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('residence_municipality_code', 5)->nullable()->after('address');
            $table->string('employee_scope', 20)->nullable()->after('department_area');

            $table->foreign('residence_municipality_code')
                ->references('municipality_code')
                ->on('colombian_municipalities')
                ->nullOnDelete();
        });

        DB::table('employees')
            ->whereNotNull('municipality_code')
            ->whereNull('residence_municipality_code')
            ->update(['residence_municipality_code' => DB::raw('municipality_code')]);

        $this->reseedEmployeeJobPositions();

        DB::table('employees')
            ->whereNull('employee_scope')
            ->orderBy('id')
            ->chunkById(200, function ($employees): void {
                foreach ($employees as $employee) {
                    $scope = null;
                    if ($employee->employee_job_position_id) {
                        $scope = DB::table('employee_job_positions')
                            ->where('id', $employee->employee_job_position_id)
                            ->value('employee_scope');
                    }

                    if ($scope === null) {
                        $legacyArea = mb_strtolower(trim((string) ($employee->department_area ?? '')));
                        $scope = in_array($legacyArea, ['operativo', 'administrativo'], true)
                            ? $legacyArea
                            : EmployeeScope::Administrativo->value;
                    }

                    DB::table('employees')->where('id', $employee->id)->update([
                        'employee_scope' => $scope,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['residence_municipality_code']);
            $table->dropColumn(['residence_municipality_code', 'employee_scope']);
        });

        Schema::table('employee_job_positions', function (Blueprint $table) {
            $table->dropColumn('employee_scope');
        });

        if (Schema::hasColumn('job_positions', 'permission_level_name')) {
            foreach (PlatformLevel::legacyMap() as $legacy => $level) {
                DB::table('job_positions')
                    ->where('permission_level_name', $level)
                    ->update(['permission_level_name' => $legacy]);
            }

            Schema::table('job_positions', function (Blueprint $table) {
                $table->renameColumn('permission_level_name', 'permission_role_name');
            });
        }

        foreach (PlatformLevel::legacyMap() as $legacy => $level) {
            DB::table('roles')
                ->where('guard_name', 'web')
                ->where('name', $level)
                ->update([
                    'name' => $legacy,
                    'level_number' => null,
                    'subtitle' => null,
                    'updated_at' => now(),
                ]);
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['level_number', 'subtitle']);
        });
    }

    private function reseedEmployeeJobPositions(): void
    {
        DB::table('employees')->update(['employee_job_position_id' => null]);
        DB::table('employee_job_positions')->delete();

        $now = now();
        $nameToId = [];

        foreach (EmployeeJobPositionsCatalog::rows() as $row) {
            $slug = EmployeeJobPosition::slugFromLabel($row['name']);
            $id = DB::table('employee_job_positions')->insertGetId([
                'slug' => $slug,
                'name' => $row['name'],
                'is_guarda' => $row['is_guarda'],
                'employee_scope' => $row['employee_scope'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $nameToId[EmployeeJobPosition::normalizeName($row['name'])] = $id;
        }

        foreach (DB::table('employees')->select(['id', 'job_title'])->orderBy('id')->cursor() as $employee) {
            $title = trim((string) ($employee->job_title ?? ''));
            if ($title === '') {
                continue;
            }

            $normalized = EmployeeJobPosition::normalizeName($title);
            $positionId = $nameToId[$normalized] ?? null;

            if ($positionId === null && str_contains($normalized, 'guarda')) {
                $positionId = $nameToId[EmployeeJobPosition::normalizeName('GUARDA DE SEGURIDAD')] ?? null;
            }

            if ($positionId === null && str_contains($normalized, 'escolta')) {
                $positionId = $nameToId[EmployeeJobPosition::normalizeName('ESCOLTA')] ?? null;
            }

            if ($positionId !== null) {
                DB::table('employees')->where('id', $employee->id)->update([
                    'employee_job_position_id' => $positionId,
                    'job_title' => DB::table('employee_job_positions')->where('id', $positionId)->value('name'),
                ]);
            }
        }
    }
};
