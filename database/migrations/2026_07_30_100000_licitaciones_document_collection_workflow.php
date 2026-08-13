<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_solicitud_invitados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('licitacion_solicitudes')->cascadeOnDelete();
            $table->string('email');
            $table->string('nombre')->nullable();
            $table->string('token', 64)->unique();
            $table->text('mensaje')->nullable();
            $table->timestamp('invitado_at')->nullable();
            $table->timestamp('notificado_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->foreignId('invitado_por_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['solicitud_id', 'email']);
        });

        Schema::table('licitacion_adjuntos', function (Blueprint $table) {
            $table->foreignId('invitado_id')->nullable()->after('user_id')
                ->constrained('licitacion_solicitud_invitados')->nullOnDelete();
            $table->string('uploader_email')->nullable()->after('invitado_id');
            $table->string('revision_estado')->default('pendiente')->after('uploader_email')->index();
            $table->text('revision_comentario')->nullable()->after('revision_estado');
            $table->foreignId('revisado_por_id')->nullable()->after('revision_comentario')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable()->after('revisado_por_id');
            $table->unsignedBigInteger('reemplaza_adjunto_id')->nullable()->after('revisado_at');
        });

        Schema::table('licitacion_adjuntos', function (Blueprint $table) {
            $table->foreign('reemplaza_adjunto_id')
                ->references('id')
                ->on('licitacion_adjuntos')
                ->nullOnDelete();
        });

        $this->makeUserIdNullable('licitacion_adjuntos');
        $this->makeUserIdNullable('licitacion_historial_actividades');

        DB::table('licitacion_adjuntos')
            ->whereNull('invitado_id')
            ->update(['revision_estado' => 'aprobado']);
    }

    public function down(): void
    {
        Schema::table('licitacion_adjuntos', function (Blueprint $table) {
            $table->dropForeign(['invitado_id']);
            $table->dropForeign(['revisado_por_id']);
            $table->dropForeign(['reemplaza_adjunto_id']);
            $table->dropColumn([
                'invitado_id',
                'uploader_email',
                'revision_estado',
                'revision_comentario',
                'revisado_por_id',
                'revisado_at',
                'reemplaza_adjunto_id',
            ]);
        });

        Schema::dropIfExists('licitacion_solicitud_invitados');
    }

    private function makeUserIdNullable(string $table): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite no soporta MODIFY; recreamos la columna como nullable.
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('user_id')->nullable()->change();
            });

            return;
        }

        $this->dropForeignIfExists($table, $table.'_user_id_foreign');
        DB::statement("ALTER TABLE `{$table}` MODIFY user_id BIGINT UNSIGNED NULL");
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
