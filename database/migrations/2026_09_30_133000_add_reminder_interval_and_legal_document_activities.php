<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_document_folders', function (Blueprint $table) {
            $table->unsignedSmallInteger('reminder_every_minutes')
                ->default(60)
                ->after('exclude_reminders');
        });

        Schema::create('legal_document_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('legal_document_folders')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('legal_document_items')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60)->index();
            $table->string('summary');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['folder_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_activities');

        Schema::table('legal_document_folders', function (Blueprint $table) {
            $table->dropColumn('reminder_every_minutes');
        });
    }
};
