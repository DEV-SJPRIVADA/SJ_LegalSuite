<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada instancia ejecutada (o programada) de una etapa del workflow.
 *
 * Un mismo caso puede tener varias etapas del mismo tipo (ej: 2 citaciones,
 * 1 reprogramación). El campo metadata (json) almacena información específica
 * de cada etapa sin tener que multiplicar tablas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('disciplinary_case_id')
                ->constrained('disciplinary_cases')->cascadeOnDelete();

            $table->string('stage_type', 32)->index();
            $table->string('form_code', 32)->nullable();
            $table->string('status', 16)->default('pendiente')->index();

            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('performed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->date('deadline_at')->nullable()
                ->comment('Para etapas con plazo legal, ej: 2 días calendario para justificar inasistencia');

            $table->foreignId('performed_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedSmallInteger('sequence')->default(1)
                ->comment('Orden cronológico de la etapa dentro del caso');

            $table->timestamps();

            $table->index(['disciplinary_case_id', 'stage_type'], 'idx_ds_case_type');
            $table->index(['disciplinary_case_id', 'sequence'], 'idx_ds_case_sequence');
            $table->index(['status', 'deadline_at'], 'idx_ds_status_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_stages');
    }
};
