<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zona de supervisión = catálogo operativo (bandeja compartida).
 * Distinto de notification_zone / decision_notification_zone (lugar físico del turno).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supervision_zones')) {
            Schema::create('supervision_zones', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('code', 40)->nullable()->unique();
                $table->string('notification_email', 190)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('supervision_zone_user')) {
            Schema::create('supervision_zone_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supervision_zone_id')
                    ->constrained('supervision_zones')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique('user_id');
                $table->unique(['supervision_zone_id', 'user_id']);
            });
        }

        Schema::table('disciplinary_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('disciplinary_cases', 'notification_supervision_zone_id')) {
                $table->unsignedBigInteger('notification_supervision_zone_id')->nullable()
                    ->after('notification_zone');
            }
            if (! Schema::hasColumn('disciplinary_cases', 'notification_supervision_zone_name')) {
                $table->string('notification_supervision_zone_name', 120)->nullable()
                    ->after('notification_supervision_zone_id');
            }
            if (! Schema::hasColumn('disciplinary_cases', 'decision_notification_supervision_zone_id')) {
                $table->unsignedBigInteger('decision_notification_supervision_zone_id')->nullable()
                    ->after('decision_notification_zone');
            }
            if (! Schema::hasColumn('disciplinary_cases', 'decision_notification_supervision_zone_name')) {
                $table->string('decision_notification_supervision_zone_name', 120)->nullable()
                    ->after('decision_notification_supervision_zone_id');
            }
        });

        $this->ensureForeignKey(
            'disciplinary_cases',
            'notification_supervision_zone_id',
            'supervision_zones',
            'disc_case_notif_sup_zone_fk',
        );
        $this->ensureForeignKey(
            'disciplinary_cases',
            'decision_notification_supervision_zone_id',
            'supervision_zones',
            'disc_case_dec_notif_sup_zone_fk',
        );

        $this->backfillFromLegacySupervisors();

        // SQLite (tests): dejar columnas legacy evita recrear tabla con FKs rotas.
        // MySQL (local/hosting): se eliminan limpiamente.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();
        try {
            if (Schema::hasColumn('disciplinary_cases', 'notification_supervisor_user_id')) {
                $this->dropForeignForColumn('disciplinary_cases', 'notification_supervisor_user_id');
                Schema::table('disciplinary_cases', function (Blueprint $table) {
                    $cols = ['notification_supervisor_user_id'];
                    if (Schema::hasColumn('disciplinary_cases', 'notification_supervisor_name')) {
                        $cols[] = 'notification_supervisor_name';
                    }
                    $table->dropColumn($cols);
                });
            }

            if (Schema::hasColumn('disciplinary_cases', 'decision_notification_supervisor_user_id')) {
                $this->dropForeignForColumn('disciplinary_cases', 'decision_notification_supervisor_user_id');
                Schema::table('disciplinary_cases', function (Blueprint $table) {
                    $cols = ['decision_notification_supervisor_user_id'];
                    if (Schema::hasColumn('disciplinary_cases', 'decision_notification_supervisor_name')) {
                        $cols[] = 'decision_notification_supervisor_name';
                    }
                    $table->dropColumn($cols);
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('disciplinary_cases', 'notification_supervisor_user_id')) {
                $table->foreignId('notification_supervisor_user_id')->nullable()
                    ->after('notification_zone')
                    ->constrained('users')->nullOnDelete();
                $table->string('notification_supervisor_name', 200)->nullable()
                    ->after('notification_supervisor_user_id');
            }

            if (! Schema::hasColumn('disciplinary_cases', 'decision_notification_supervisor_user_id')) {
                $table->foreignId('decision_notification_supervisor_user_id')->nullable()
                    ->after('decision_notification_zone')
                    ->constrained('users')->nullOnDelete();
                $table->string('decision_notification_supervisor_name', 200)->nullable()
                    ->after('decision_notification_supervisor_user_id');
            }
        });

        $this->dropForeignIfExists('disciplinary_cases', 'disc_case_notif_sup_zone_fk');
        $this->dropForeignIfExists('disciplinary_cases', 'disc_case_dec_notif_sup_zone_fk');

        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $cols = [];
            foreach ([
                'notification_supervision_zone_id',
                'notification_supervision_zone_name',
                'decision_notification_supervision_zone_id',
                'decision_notification_supervision_zone_name',
            ] as $col) {
                if (Schema::hasColumn('disciplinary_cases', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('supervision_zone_user');
        Schema::dropIfExists('supervision_zones');
    }

    private function dropForeignForColumn(string $table, string $column): void
    {
        foreach ($this->foreignKeyNamesForColumn($table, $column) as $name) {
            $this->dropForeignIfExists($table, $name);
        }
    }

    private function ensureForeignKey(string $table, string $column, string $refTable, string $name): void
    {
        if ($this->foreignKeyExists($table, $name)) {
            return;
        }

        // Puede existir el FK auto-nombrado de un intento fallido/parcial.
        $auto = $table.'_'.$column.'_foreign';
        $this->dropForeignIfExists($table, $auto);

        Schema::table($table, function (Blueprint $blueprint) use ($column, $refTable, $name): void {
            $blueprint->foreign($column, $name)
                ->references('id')
                ->on($refTable)
                ->nullOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $name): void
    {
        if (! $this->foreignKeyExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropForeign($name);
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        foreach ($this->listForeignKeys($table) as $fk) {
            if (($fk['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function foreignKeyNamesForColumn(string $table, string $column): array
    {
        $names = [];
        foreach ($this->listForeignKeys($table) as $fk) {
            $cols = $fk['columns'] ?? [];
            if (in_array($column, $cols, true)) {
                $names[] = (string) ($fk['name'] ?? '');
            }
        }

        return array_values(array_filter($names));
    }

    /**
     * @return list<array{name?: string, columns?: list<string>}>
     */
    private function listForeignKeys(string $table): array
    {
        try {
            /** @var list<array{name?: string, columns?: list<string>}> $keys */
            $keys = Schema::getForeignKeys($table);

            return $keys;
        } catch (\Throwable) {
            return [];
        }
    }

    private function backfillFromLegacySupervisors(): void
    {
        if (! Schema::hasColumn('disciplinary_cases', 'notification_supervisor_user_id')) {
            return;
        }

        $userIds = DB::table('disciplinary_cases')
            ->whereNotNull('notification_supervisor_user_id')
            ->pluck('notification_supervisor_user_id');

        if (Schema::hasColumn('disciplinary_cases', 'decision_notification_supervisor_user_id')) {
            $userIds = $userIds->merge(
                DB::table('disciplinary_cases')
                    ->whereNotNull('decision_notification_supervisor_user_id')
                    ->pluck('decision_notification_supervisor_user_id')
            );
        }

        $userIds = $userIds->unique()->filter()->values();
        $zoneByUser = [];

        foreach ($userIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user === null) {
                continue;
            }

            $existingPivot = DB::table('supervision_zone_user')->where('user_id', $userId)->first();
            if ($existingPivot) {
                $zoneByUser[(int) $userId] = (int) $existingPivot->supervision_zone_id;
                continue;
            }

            $zoneId = DB::table('supervision_zones')->insertGetId([
                'name' => 'Zona · '.((string) ($user->name ?? 'Supervisor '.$userId)),
                'code' => null,
                'notification_email' => null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('supervision_zone_user')->insert([
                'supervision_zone_id' => $zoneId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $zoneByUser[(int) $userId] = $zoneId;
        }

        $zoneNames = DB::table('supervision_zones')->pluck('name', 'id');

        foreach (DB::table('disciplinary_cases')->whereNotNull('notification_supervisor_user_id')->cursor() as $case) {
            $zoneId = $zoneByUser[(int) $case->notification_supervisor_user_id] ?? null;
            if ($zoneId === null) {
                continue;
            }
            DB::table('disciplinary_cases')->where('id', $case->id)->update([
                'notification_supervision_zone_id' => $zoneId,
                'notification_supervision_zone_name' => $zoneNames[$zoneId] ?? null,
            ]);
        }

        if (Schema::hasColumn('disciplinary_cases', 'decision_notification_supervisor_user_id')) {
            foreach (DB::table('disciplinary_cases')->whereNotNull('decision_notification_supervisor_user_id')->cursor() as $case) {
                $zoneId = $zoneByUser[(int) $case->decision_notification_supervisor_user_id] ?? null;
                if ($zoneId === null) {
                    continue;
                }
                DB::table('disciplinary_cases')->where('id', $case->id)->update([
                    'decision_notification_supervision_zone_id' => $zoneId,
                    'decision_notification_supervision_zone_name' => $zoneNames[$zoneId] ?? null,
                ]);
            }
        }
    }
};
