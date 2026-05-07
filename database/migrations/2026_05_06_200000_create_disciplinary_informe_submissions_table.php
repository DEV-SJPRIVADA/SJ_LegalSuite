<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_informe_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->string('status', 40);
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_path', 2048);
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->json('form_snapshot')->nullable();
            $table->json('evidence_paths')->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->foreignId('disciplinary_case_id')->nullable()->constrained('disciplinary_cases')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_informe_submissions');
    }
};
