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
            $table->string('email_notificacion')->nullable()->after('created_by_id');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                UPDATE licitacion_solicitudes
                SET email_notificacion = (
                    SELECT email FROM users WHERE users.id = licitacion_solicitudes.created_by_id
                )
                WHERE email_notificacion IS NULL
            ');
        } else {
            DB::statement('
                UPDATE licitacion_solicitudes s
                INNER JOIN users u ON u.id = s.created_by_id
                SET s.email_notificacion = u.email
                WHERE s.email_notificacion IS NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('licitacion_solicitudes', function (Blueprint $table) {
            $table->dropColumn('email_notificacion');
        });
    }
};
