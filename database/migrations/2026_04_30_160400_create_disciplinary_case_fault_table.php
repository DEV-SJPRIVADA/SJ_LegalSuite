<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_case_fault', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')
                ->constrained('disciplinary_cases')->cascadeOnDelete();
            $table->foreignId('fault_id')
                ->constrained('faults')->restrictOnDelete();
            $table->text('extra_info')->nullable()
                ->comment('Detalle obligatorio cuando la falta es "Otros"');
            $table->timestamps();

            $table->unique(['disciplinary_case_id', 'fault_id'], 'uniq_case_fault');
            $table->index('fault_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_case_fault');
    }
};
