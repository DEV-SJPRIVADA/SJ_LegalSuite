<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla raíz del módulo. Contiene el estado denormalizado (current_status, current_stage_type)
 * para que el dashboard y los listados sean rápidos sin recalcular sobre stages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();

            $table->string('case_number', 32)->unique()
                ->comment('Consecutivo legible: DISC-2026-000001');

            $table->foreignId('personnel_id')
                ->constrained('personnel')->restrictOnDelete();

            $table->foreignId('reporter_id')->nullable()
                ->comment('Usuario que creó el informe (área administrativa/operaciones)')
                ->constrained('users')->nullOnDelete();

            $table->foreignId('assigned_lawyer_id')->nullable()
                ->comment('Abogado responsable del caso')
                ->constrained('users')->nullOnDelete();

            $table->string('city', 100)->nullable();
            $table->string('sede', 120)->nullable();

            $table->string('current_status', 40)->index();
            $table->string('current_stage_type', 32)->nullable()->index();

            $table->string('decision', 40)->nullable();
            $table->text('decision_notes')->nullable();
            $table->date('decided_at')->nullable();

            $table->date('opened_at');
            $table->date('closed_at')->nullable();
            $table->text('summary')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['current_status', 'assigned_lawyer_id'], 'idx_dc_status_lawyer');
            $table->index(['current_status', 'city'], 'idx_dc_status_city');
            $table->index(['assigned_lawyer_id', 'opened_at'], 'idx_dc_lawyer_opened');
            $table->index('opened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_cases');
    }
};
