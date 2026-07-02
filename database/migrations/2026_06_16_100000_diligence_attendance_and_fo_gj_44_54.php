<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->string('diligence_attendance')->nullable()->after('fo_gj_04_generated_by');
            $table->timestamp('diligence_attendance_registered_at')->nullable()->after('diligence_attendance');
            $table->foreignId('diligence_attendance_registered_by')->nullable()->after('diligence_attendance_registered_at')->constrained('users')->nullOnDelete();

            $table->json('fo_gj_44_payload')->nullable()->after('diligence_attendance_registered_by');
            $table->timestamp('fo_gj_44_draft_completed_at')->nullable()->after('fo_gj_44_payload');
            $table->foreignId('fo_gj_44_draft_completed_by')->nullable()->after('fo_gj_44_draft_completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('fo_gj_44_generated_at')->nullable()->after('fo_gj_44_draft_completed_by');
            $table->foreignId('fo_gj_44_generated_by')->nullable()->after('fo_gj_44_generated_at')->constrained('users')->nullOnDelete();

            $table->json('fo_gj_54_payload')->nullable()->after('fo_gj_44_generated_by');
            $table->timestamp('fo_gj_54_draft_completed_at')->nullable()->after('fo_gj_54_payload');
            $table->foreignId('fo_gj_54_draft_completed_by')->nullable()->after('fo_gj_54_draft_completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('fo_gj_54_generated_at')->nullable()->after('fo_gj_54_draft_completed_by');
            $table->foreignId('fo_gj_54_generated_by')->nullable()->after('fo_gj_54_generated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fo_gj_54_generated_by');
            $table->dropConstrainedForeignId('fo_gj_54_draft_completed_by');
            $table->dropColumn([
                'fo_gj_54_payload',
                'fo_gj_54_draft_completed_at',
                'fo_gj_54_generated_at',
            ]);

            $table->dropConstrainedForeignId('fo_gj_44_generated_by');
            $table->dropConstrainedForeignId('fo_gj_44_draft_completed_by');
            $table->dropConstrainedForeignId('diligence_attendance_registered_by');
            $table->dropColumn([
                'fo_gj_44_payload',
                'fo_gj_44_draft_completed_at',
                'fo_gj_44_generated_at',
                'diligence_attendance',
                'diligence_attendance_registered_at',
            ]);
        });
    }
};
