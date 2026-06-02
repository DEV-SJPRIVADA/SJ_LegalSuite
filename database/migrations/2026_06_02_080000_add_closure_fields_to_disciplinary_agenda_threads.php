<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinary_agenda_threads', function (Blueprint $table) {
            $table->string('coordination_status', 20)
                ->default('open')
                ->after('planning_replied_at')
                ->index();
            $table->timestamp('closed_at')->nullable()->after('coordination_status');
            $table->foreignId('closed_by')
                ->nullable()
                ->after('closed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_agenda_threads', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['coordination_status', 'closed_at', 'closed_by']);
        });
    }
};
