<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->json('fo_gj_04_payload')->nullable()->after('citation_evidence_type');
            $table->timestamp('fo_gj_04_draft_completed_at')->nullable()->after('fo_gj_04_payload');
            $table->foreignId('fo_gj_04_draft_completed_by')->nullable()->after('fo_gj_04_draft_completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('fo_gj_04_generated_at')->nullable()->after('fo_gj_04_draft_completed_by');
            $table->foreignId('fo_gj_04_generated_by')->nullable()->after('fo_gj_04_generated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropForeign(['fo_gj_04_draft_completed_by']);
            $table->dropForeign(['fo_gj_04_generated_by']);
            $table->dropColumn([
                'fo_gj_04_payload',
                'fo_gj_04_draft_completed_at',
                'fo_gj_04_draft_completed_by',
                'fo_gj_04_generated_at',
                'fo_gj_04_generated_by',
            ]);
        });
    }
};
