<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->json('fo_gj_03_payload')->nullable()->after('fo_gj_03_generated_by');
            $table->timestamp('fo_gj_03_draft_completed_at')->nullable()->after('fo_gj_03_payload');
            $table->foreignId('fo_gj_03_draft_completed_by')->nullable()->after('fo_gj_03_draft_completed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path', 512)->nullable()->after('theme');
            $table->string('signature_disk', 32)->nullable()->default('local')->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropForeign(['fo_gj_03_draft_completed_by']);
            $table->dropColumn([
                'fo_gj_03_payload',
                'fo_gj_03_draft_completed_at',
                'fo_gj_03_draft_completed_by',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'signature_disk']);
        });
    }
};
