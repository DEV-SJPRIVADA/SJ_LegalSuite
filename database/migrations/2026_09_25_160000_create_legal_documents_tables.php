<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_folders', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name');
            $table->boolean('exclude_reminders')->default(false)->index();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsible_email')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('legal_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('legal_document_folders')->cascadeOnDelete();
            $table->string('code', 40)->nullable()->index();
            $table->string('group_title')->nullable();
            $table->string('title');
            $table->string('issued_by')->nullable();
            $table->date('issued_on')->nullable();
            $table->string('frequency', 80)->nullable();
            $table->date('renew_on')->nullable()->index();
            $table->string('renew_label')->nullable();
            $table->text('observations')->nullable();
            $table->string('source', 20)->default('matrix'); // matrix|manual
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reminder_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['folder_id', 'is_active']);
        });

        Schema::create('legal_document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('legal_document_items')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('item_id'); // una versión vigente por requisito
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_files');
        Schema::dropIfExists('legal_document_items');
        Schema::dropIfExists('legal_document_folders');
    }
};
