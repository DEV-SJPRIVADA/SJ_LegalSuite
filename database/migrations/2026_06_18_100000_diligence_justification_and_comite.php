<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->timestamp('diligence_justification_received_at')->nullable()->after('fo_gj_54_generated_by');
            $table->foreignId('diligence_justification_received_by')->nullable()->after('diligence_justification_received_at')->constrained('users')->nullOnDelete();
            $table->text('diligence_justification_notes')->nullable()->after('diligence_justification_received_by');

            $table->json('comite_payload')->nullable()->after('diligence_justification_notes');
            $table->timestamp('comite_draft_completed_at')->nullable()->after('comite_payload');
            $table->foreignId('comite_draft_completed_by')->nullable()->after('comite_draft_completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('comite_generated_at')->nullable()->after('comite_draft_completed_by');
            $table->foreignId('comite_generated_by')->nullable()->after('comite_generated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comite_generated_by');
            $table->dropConstrainedForeignId('comite_draft_completed_by');
            $table->dropConstrainedForeignId('diligence_justification_received_by');
            $table->dropColumn([
                'diligence_justification_received_at',
                'diligence_justification_notes',
                'comite_payload',
                'comite_draft_completed_at',
                'comite_generated_at',
            ]);
        });
    }
};
