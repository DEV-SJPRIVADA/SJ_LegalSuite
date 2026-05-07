<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extensión del modelo usuario para SJ LegalSuite:
 * datos de contacto y perfil, tema, banderas de acceso y estructura organizacional (áreas / cargos).
 *
 * Cada cargo enlaza opcionalmente al nombre de un rol Spatie (`permission_role_name`) para el paquete de permisos.
 * Las áreas son el ámbito organizacional; supervisor/operador/programador son cargos, no áreas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document_number', 32)->nullable()->after('email')->index();
            $table->string('phone', 32)->nullable()->after('document_number');
            $table->string('area', 32)->nullable()->after('phone')->index();
            $table->string('position', 120)->nullable()->after('area');
            $table->boolean('is_active')->default(true)->after('position')->index();
            $table->boolean('read_only')->default(false)->after('is_active')->index();
            $table->boolean('must_change_password')->default(false)->after('read_only')->index();
            $table->string('theme', 16)->default('light')->after('must_change_password');
            $table->softDeletes();
        });

        Schema::create('organizational_areas', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_area_id')->constrained('organizational_areas')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('permission_role_name', 64)->nullable()->index()
                ->comment('Nombre del rol Spatie (guard web) que define permisos para usuarios con este cargo');
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizational_area_id')->nullable()->after('area')->constrained('organizational_areas')->nullOnDelete();
            $table->foreignId('job_position_id')->nullable()->after('position')->constrained('job_positions')->nullOnDelete();
        });

        $now = now();

        foreach ([
            ['slug' => 'juridica', 'name' => 'Jurídica', 'sort_order' => 10],
            ['slug' => 'operaciones', 'name' => 'Operaciones', 'sort_order' => 20],
            ['slug' => 'planeacion', 'name' => 'Planeación', 'sort_order' => 30],
            ['slug' => 'administrativa', 'name' => 'Administrativa', 'sort_order' => 40],
            ['slug' => 'gerencia', 'name' => 'Gerencia', 'sort_order' => 50],
        ] as $row) {
            DB::table('organizational_areas')->insert([
                'slug' => $row['slug'],
                'name' => $row['name'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $slugToAreaId = DB::table('organizational_areas')->pluck('id', 'slug')->all();

        foreach (DB::table('users')->whereNotNull('area')->cursor() as $userRow) {
            $slug = (string) $userRow->area;
            $areaId = $slugToAreaId[$slug] ?? null;
            if ($areaId === null) {
                continue;
            }
            DB::table('users')->where('id', $userRow->id)->update([
                'organizational_area_id' => $areaId,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            ['slug' => 'juridica', 'name' => 'Abogado asignado', 'sort_order' => 10, 'permission_role_name' => 'abogado'],
            ['slug' => 'operaciones', 'name' => 'Dirección operaciones', 'sort_order' => 10, 'permission_role_name' => 'operaciones'],
            ['slug' => 'operaciones', 'name' => 'Supervisor', 'sort_order' => 20, 'permission_role_name' => 'supervisor'],
            ['slug' => 'operaciones', 'name' => 'Operador', 'sort_order' => 30, 'permission_role_name' => 'operador'],
            ['slug' => 'planeacion', 'name' => 'Dirección planeación', 'sort_order' => 10, 'permission_role_name' => 'planeacion'],
            ['slug' => 'planeacion', 'name' => 'Programador', 'sort_order' => 20, 'permission_role_name' => 'programador'],
            ['slug' => 'administrativa', 'name' => 'Coordinación administrativa', 'sort_order' => 10, 'permission_role_name' => 'administrativa'],
            ['slug' => 'gerencia', 'name' => 'Auditoría', 'sort_order' => 10, 'permission_role_name' => 'auditor'],
        ] as $pos) {
            $areaId = $slugToAreaId[$pos['slug']] ?? null;
            if ($areaId === null) {
                continue;
            }
            DB::table('job_positions')->insert([
                'organizational_area_id' => $areaId,
                'name' => $pos['name'],
                'permission_role_name' => $pos['permission_role_name'],
                'sort_order' => $pos['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_position_id');
            $table->dropConstrainedForeignId('organizational_area_id');
        });

        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('organizational_areas');

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'theme',
                'must_change_password',
                'read_only',
                'is_active',
                'position',
                'area',
                'phone',
                'document_number',
            ]);
        });
    }
};
