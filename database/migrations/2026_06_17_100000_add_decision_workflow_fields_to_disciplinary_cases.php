<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('disciplinary_cases', 'decision_coordination_started_at')) {
            $anchor = $this->decisionWorkflowAnchorColumn();

            Schema::table('disciplinary_cases', function (Blueprint $table) use ($anchor) {
                $coordinationStarted = $table->timestamp('decision_coordination_started_at')->nullable();
                if ($anchor !== null) {
                    $coordinationStarted->after($anchor);
                }
                $table->unsignedBigInteger('decision_coordination_started_by')->nullable()->after('decision_coordination_started_at');

                $table->json('decision_payload')->nullable()->after('decision_coordination_started_by');
                $table->timestamp('decision_draft_completed_at')->nullable()->after('decision_payload');
                $table->unsignedBigInteger('decision_draft_completed_by')->nullable()->after('decision_draft_completed_at');
                $table->timestamp('decision_comunicado_generated_at')->nullable()->after('decision_draft_completed_by');
                $table->unsignedBigInteger('decision_comunicado_generated_by')->nullable()->after('decision_comunicado_generated_at');

                $table->timestamp('decision_notification_completed_at')->nullable()->after('decision_comunicado_generated_by');
                $table->unsignedBigInteger('decision_notification_message_id')->nullable()->after('decision_notification_completed_at');
                $table->date('decision_notification_date')->nullable()->after('decision_notification_message_id');
                $table->string('decision_notification_shift', 80)->nullable()->after('decision_notification_date');
                $table->string('decision_notification_zone', 120)->nullable()->after('decision_notification_shift');
                $table->unsignedBigInteger('decision_notification_supervisor_user_id')->nullable()->after('decision_notification_zone');
                $table->string('decision_notification_supervisor_name')->nullable()->after('decision_notification_supervisor_user_id');
                $table->text('decision_notification_notes')->nullable()->after('decision_notification_supervisor_name');
                $table->timestamp('decision_notification_supervisor_assigned_at')->nullable()->after('decision_notification_notes');
                $table->unsignedBigInteger('decision_notification_supervisor_assigned_by')->nullable()->after('decision_notification_supervisor_assigned_at');

                $table->string('decision_evidence_type', 40)->nullable()->after('decision_notification_supervisor_assigned_by');
                $table->timestamp('decision_evidence_uploaded_at')->nullable()->after('decision_evidence_type');

                $table->timestamp('decision_hr_review_completed_at')->nullable()->after('decision_evidence_uploaded_at');
                $table->unsignedBigInteger('decision_hr_review_completed_by')->nullable()->after('decision_hr_review_completed_at');
            });
        }

        $this->addForeignKeyIfMissing('decision_coordination_started_by', 'disc_case_dec_coord_by_fk');
        $this->addForeignKeyIfMissing('decision_draft_completed_by', 'disc_case_dec_draft_by_fk');
        $this->addForeignKeyIfMissing('decision_comunicado_generated_by', 'disc_case_dec_com_gen_by_fk');
        $this->addForeignKeyIfMissing('decision_notification_supervisor_user_id', 'disc_case_dec_notif_sup_fk');
        $this->addForeignKeyIfMissing('decision_notification_supervisor_assigned_by', 'disc_case_dec_notif_asg_fk');
        $this->addForeignKeyIfMissing('decision_hr_review_completed_by', 'disc_case_dec_hr_by_fk');
    }

    public function down(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            foreach ([
                'disc_case_dec_coord_by_fk',
                'disc_case_dec_draft_by_fk',
                'disc_case_dec_com_gen_by_fk',
                'disc_case_dec_notif_sup_fk',
                'disc_case_dec_notif_asg_fk',
                'disc_case_dec_hr_by_fk',
            ] as $fk) {
                if ($this->foreignKeyExists($fk)) {
                    $table->dropForeign($fk);
                }
            }

            if (Schema::hasColumn('disciplinary_cases', 'decision_coordination_started_at')) {
                $table->dropColumn([
                    'decision_coordination_started_at',
                    'decision_coordination_started_by',
                    'decision_payload',
                    'decision_draft_completed_at',
                    'decision_draft_completed_by',
                    'decision_comunicado_generated_at',
                    'decision_comunicado_generated_by',
                    'decision_notification_completed_at',
                    'decision_notification_message_id',
                    'decision_notification_date',
                    'decision_notification_shift',
                    'decision_notification_zone',
                    'decision_notification_supervisor_user_id',
                    'decision_notification_supervisor_name',
                    'decision_notification_notes',
                    'decision_notification_supervisor_assigned_at',
                    'decision_notification_supervisor_assigned_by',
                    'decision_evidence_type',
                    'decision_evidence_uploaded_at',
                    'decision_hr_review_completed_at',
                    'decision_hr_review_completed_by',
                ]);
            }
        });
    }

    private function decisionWorkflowAnchorColumn(): ?string
    {
        foreach ([
            'comite_generated_by',
            'fo_gj_54_generated_by',
            'fo_gj_44_generated_by',
            'diligence_attendance',
        ] as $column) {
            if (Schema::hasColumn('disciplinary_cases', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function addForeignKeyIfMissing(string $column, string $name): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasColumn('disciplinary_cases', $column) || $this->foreignKeyExists($name)) {
            return;
        }

        Schema::table('disciplinary_cases', function (Blueprint $table) use ($column, $name) {
            $table->foreign($column, $name)->references('id')->on('users')->nullOnDelete();
        });
    }

    private function foreignKeyExists(string $name): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }

        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$database, 'disciplinary_cases', $name, 'FOREIGN KEY'],
        );

        return $result !== null;
    }
};
