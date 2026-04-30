<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora inmutable de actuaciones del caso (audit log).
 *
 * Cada cambio relevante (transición de estado, subida de documento, comentario,
 * etc.) genera un registro aquí. Esto soporta trazabilidad legal y auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('disciplinary_case_id')
                ->constrained('disciplinary_cases')->cascadeOnDelete();

            $table->foreignId('disciplinary_stage_id')->nullable()
                ->constrained('disciplinary_stages')->nullOnDelete();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('action_type', 48)->index();

            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();

            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->dateTime('performed_at')->index();
            $table->timestamps();

            $table->index(['disciplinary_case_id', 'performed_at'], 'idx_da_case_performed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_actions');
    }
};
