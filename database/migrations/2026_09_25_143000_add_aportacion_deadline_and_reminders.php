<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacion_solicitudes', function (Blueprint $table) {
            $table->dateTime('aportacion_limite_at')->nullable()->after('fecha_limite')->index();
        });

        // Backfill: fecha límite de la solicitud a las 17:00 (horario laboral).
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE licitacion_solicitudes
                SET aportacion_limite_at = fecha_limite || ' 17:00:00'
                WHERE aportacion_limite_at IS NULL AND fecha_limite IS NOT NULL
            ");
        } else {
            DB::statement("
                UPDATE licitacion_solicitudes
                SET aportacion_limite_at = CONCAT(fecha_limite, ' 17:00:00')
                WHERE aportacion_limite_at IS NULL AND fecha_limite IS NOT NULL
            ");
        }

        Schema::table('licitacion_solicitud_invitados', function (Blueprint $table) {
            $table->timestamp('ultimo_recordatorio_at')->nullable()->after('notificado_at');
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_solicitud_invitados', function (Blueprint $table) {
            $table->dropColumn('ultimo_recordatorio_at');
        });

        Schema::table('licitacion_solicitudes', function (Blueprint $table) {
            $table->dropColumn('aportacion_limite_at');
        });
    }
};
