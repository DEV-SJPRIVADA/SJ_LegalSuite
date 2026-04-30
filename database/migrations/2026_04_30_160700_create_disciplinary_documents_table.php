<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('disciplinary_case_id')
                ->constrained('disciplinary_cases')->cascadeOnDelete();

            $table->foreignId('disciplinary_stage_id')->nullable()
                ->constrained('disciplinary_stages')->nullOnDelete();

            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('document_type', 40)->index();
            $table->string('form_code', 32)->nullable();

            $table->string('original_name', 255);
            $table->string('disk', 32)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable()->index();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['disciplinary_case_id', 'document_type'], 'idx_dd_case_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_documents');
    }
};
