<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_job_positions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->boolean('is_guarda')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_authorized_municipalities', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('municipality_code', 5);
            $table->foreign('municipality_code')
                ->references('municipality_code')
                ->on('colombian_municipalities')
                ->cascadeOnDelete();
            $table->primary(['user_id', 'municipality_code']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('employee_job_position_id')
                ->nullable()
                ->after('job_title')
                ->constrained('employee_job_positions')
                ->nullOnDelete();
        });

        $this->seedEmployeeJobPositions();
        $this->backfillEmployeeJobPositions();
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_job_position_id');
        });

        Schema::dropIfExists('user_authorized_municipalities');
        Schema::dropIfExists('employee_job_positions');
    }

    private function seedEmployeeJobPositions(): void
    {
        $now = now();
        $rows = [
            ['slug' => 'guarda-seguridad', 'name' => 'Guarda de seguridad', 'is_guarda' => true, 'sort_order' => 10],
            ['slug' => 'supervisor-turno', 'name' => 'Supervisor de turno', 'is_guarda' => false, 'sort_order' => 20],
            ['slug' => 'auxiliar-servicios', 'name' => 'Auxiliar de servicios', 'is_guarda' => false, 'sort_order' => 30],
            ['slug' => 'operario', 'name' => 'Operario', 'is_guarda' => false, 'sort_order' => 40],
        ];

        foreach ($rows as $row) {
            DB::table('employee_job_positions')->insert([
                ...$row,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillEmployeeJobPositions(): void
    {
        $positions = DB::table('employee_job_positions')->get(['id', 'name', 'is_guarda']);
        $byNormalizedName = [];
        foreach ($positions as $position) {
            $byNormalizedName[$this->normalizeLabel((string) $position->name)] = (int) $position->id;
        }

        $guardaId = (int) (DB::table('employee_job_positions')->where('slug', 'guarda-seguridad')->value('id') ?? 0);

        DB::table('employees')
            ->select(['id', 'job_title'])
            ->orderBy('id')
            ->chunkById(200, function ($employees) use ($byNormalizedName, $guardaId): void {
                foreach ($employees as $employee) {
                    $title = trim((string) ($employee->job_title ?? ''));
                    $positionId = null;

                    if ($title !== '') {
                        $normalized = $this->normalizeLabel($title);
                        $positionId = $byNormalizedName[$normalized] ?? null;

                        if ($positionId === null && $guardaId > 0 && str_contains($normalized, 'guarda')) {
                            $positionId = $guardaId;
                        }
                    }

                    if ($positionId !== null) {
                        DB::table('employees')->where('id', $employee->id)->update([
                            'employee_job_position_id' => $positionId,
                        ]);
                    }
                }
            });
    }

    private function normalizeLabel(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $v);

        return preg_replace('/\s+/u', ' ', $v) ?? $v;
    }
};
