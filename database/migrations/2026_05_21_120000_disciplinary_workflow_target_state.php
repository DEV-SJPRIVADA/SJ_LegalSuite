<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado objetivo Etapas A y B: revisor operaciones asignado, citación estructurada y evidencias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_informe_submissions', function (Blueprint $table) {
            $table->foreignId('assigned_reviewer_id')
                ->nullable()
                ->after('submitted_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['status', 'assigned_reviewer_id'], 'idx_dis_status_reviewer');
        });

        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->timestamp('coordination_started_at')->nullable()->after('summary');
            $table->date('citation_confirmed_date')->nullable();
            $table->time('citation_confirmed_time')->nullable();
            $table->foreignId('citation_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('citation_selected_message_id')->nullable();
            $table->timestamp('fo_gj_03_generated_at')->nullable();
            $table->foreignId('fo_gj_03_generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('citation_evidence_type', 32)->nullable()->comment('signed|refused_witnesses');
            $table->timestamp('citation_evidence_uploaded_at')->nullable();
        });

        Schema::table('disciplinary_agenda_threads', function (Blueprint $table) {
            $table->timestamp('coordination_started_at')->nullable()->after('opened_by');
        });

        Schema::table('disciplinary_agenda_messages', function (Blueprint $table) {
            $table->string('message_kind', 32)->default('general')->after('user_id');
            $table->json('proposed_slots')->nullable()->after('body');
        });

        Schema::table('disciplinary_actions', function (Blueprint $table) {
            $table->foreignId('disciplinary_case_id')->nullable()->change();
            $table->foreignId('informe_submission_id')
                ->nullable()
                ->after('disciplinary_case_id')
                ->constrained('disciplinary_informe_submissions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_actions', function (Blueprint $table) {
            $table->dropForeign(['informe_submission_id']);
            $table->dropColumn('informe_submission_id');
        });

        Schema::table('disciplinary_agenda_messages', function (Blueprint $table) {
            $table->dropColumn(['message_kind', 'proposed_slots']);
        });

        Schema::table('disciplinary_agenda_threads', function (Blueprint $table) {
            $table->dropColumn('coordination_started_at');
        });

        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropForeign(['citation_confirmed_by']);
            $table->dropForeign(['fo_gj_03_generated_by']);
            $table->dropColumn([
                'coordination_started_at',
                'citation_confirmed_date',
                'citation_confirmed_time',
                'citation_confirmed_by',
                'citation_selected_message_id',
                'fo_gj_03_generated_at',
                'fo_gj_03_generated_by',
                'citation_evidence_type',
                'citation_evidence_uploaded_at',
            ]);
        });

        Schema::table('disciplinary_informe_submissions', function (Blueprint $table) {
            $table->dropForeign(['assigned_reviewer_id']);
            $table->dropColumn('assigned_reviewer_id');
        });
    }
};
