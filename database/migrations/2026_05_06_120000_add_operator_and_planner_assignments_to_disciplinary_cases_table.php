<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->foreignId('assigned_operator_id')
                ->nullable()
                ->after('assigned_lawyer_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('assigned_planner_id')
                ->nullable()
                ->after('assigned_operator_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['assigned_operator_id', 'opened_at'], 'idx_dc_operator_opened');
            $table->index(['assigned_planner_id', 'opened_at'], 'idx_dc_planner_opened');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropIndex('idx_dc_operator_opened');
            $table->dropIndex('idx_dc_planner_opened');
            $table->dropConstrainedForeignId('assigned_planner_id');
            $table->dropConstrainedForeignId('assigned_operator_id');
        });
    }
};
