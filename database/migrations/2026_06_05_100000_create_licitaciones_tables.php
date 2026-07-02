<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsable_principal_id')->constrained('users')->restrictOnDelete();
            $table->string('entidad_contratante')->nullable();
            $table->string('modalidad_contratacion')->nullable();
            $table->string('numero_proceso')->nullable()->index();
            $table->text('objeto')->nullable();
            $table->string('cuantia')->nullable();
            $table->string('plazo_ejecucion')->nullable();
            $table->string('lugar_ejecucion')->nullable();
            $table->string('medio_presentacion')->nullable();
            $table->string('enlace_proceso', 2048)->nullable();
            $table->string('participacion_tipo')->nullable();
            $table->text('integrantes_participacion')->nullable();
            $table->date('fecha_cierre_oferta')->nullable();
            $table->time('hora_cierre_oferta')->nullable();
            $table->date('fecha_observaciones_evaluacion')->nullable();
            $table->date('fecha_adjudicacion')->nullable();
            $table->string('cumplimos')->nullable();
            $table->text('motivo_no_cumplir')->nullable();
            $table->string('estado_proceso')->nullable()->index();
            $table->string('resultado')->nullable();
            $table->timestamps();
        });

        Schema::create('licitacion_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licitacion_id')->nullable()->constrained('licitaciones')->nullOnDelete();
            $table->string('numero_radicado')->unique();
            $table->date('fecha_creacion');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('area_responsable');
            $table->foreignId('usuario_responsable_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo_solicitud');
            $table->string('periodicidad')->nullable();
            $table->string('tipo_peticion');
            $table->date('fecha_limite');
            $table->string('estado')->default('recibido')->index();
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->string('archivo_adjunto', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('licitacion_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->nullable()->constrained('licitacion_solicitudes')->cascadeOnDelete();
            $table->foreignId('licitacion_id')->nullable()->constrained('licitaciones')->cascadeOnDelete();
            $table->foreignId('comentario_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nombre_archivo');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamaño_archivo')->nullable();
            $table->timestamps();
        });

        Schema::create('licitacion_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('licitacion_solicitudes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('comentario');
            $table->timestamps();
        });

        Schema::table('licitacion_adjuntos', function (Blueprint $table) {
            $table->foreign('comentario_id')->references('id')->on('licitacion_comentarios')->cascadeOnDelete();
        });

        Schema::create('licitacion_historial_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('licitacion_solicitudes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('accion');
            $table->json('detalles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_historial_actividades');
        Schema::dropIfExists('licitacion_adjuntos');
        Schema::dropIfExists('licitacion_comentarios');
        Schema::dropIfExists('licitacion_solicitudes');
        Schema::dropIfExists('licitaciones');
    }
};
