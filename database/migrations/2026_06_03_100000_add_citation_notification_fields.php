<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->timestamp('notification_requested_at')->nullable()->after('citation_evidence_uploaded_at');
            $table->foreignId('notification_requested_by')->nullable()->after('notification_requested_at')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('notification_information_completed_at')->nullable()->after('notification_requested_by');
            $table->foreignId('notification_information_message_id')->nullable()->after('notification_information_completed_at')
                ->constrained('disciplinary_agenda_messages')->nullOnDelete();

            $table->date('notification_date')->nullable()->after('notification_information_message_id');
            $table->string('notification_shift', 80)->nullable()->after('notification_date');
            $table->string('notification_zone', 120)->nullable()->after('notification_shift');

            $table->foreignId('notification_supervisor_user_id')->nullable()->after('notification_zone')
                ->constrained('users')->nullOnDelete();
            $table->string('notification_supervisor_name', 200)->nullable()->after('notification_supervisor_user_id');
            $table->text('notification_notes')->nullable()->after('notification_supervisor_name');

            $table->timestamp('notification_supervisor_assigned_at')->nullable()->after('notification_notes');
            $table->foreignId('notification_supervisor_assigned_by')->nullable()->after('notification_supervisor_assigned_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('disciplinary_agenda_messages', function (Blueprint $table) {
            $table->json('notification_payload')->nullable()->after('proposed_slots');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_agenda_messages', function (Blueprint $table) {
            $table->dropColumn('notification_payload');
        });

        Schema::table('disciplinary_cases', function (Blueprint $table) {
            $table->dropForeign(['notification_requested_by']);
            $table->dropForeign(['notification_information_message_id']);
            $table->dropForeign(['notification_supervisor_user_id']);
            $table->dropForeign(['notification_supervisor_assigned_by']);
            $table->dropColumn([
                'notification_requested_at',
                'notification_requested_by',
                'notification_information_completed_at',
                'notification_information_message_id',
                'notification_date',
                'notification_shift',
                'notification_zone',
                'notification_supervisor_user_id',
                'notification_supervisor_name',
                'notification_notes',
                'notification_supervisor_assigned_at',
                'notification_supervisor_assigned_by',
            ]);
        });
    }
};
