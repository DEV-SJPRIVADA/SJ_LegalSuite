<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hilo de solicitud de agenda (Etapa A): abogado ↔ planeación/programador, con adjuntos.
 * `planning_replied_at` marca la primera respuesta del lado planeación (requisito para pasar a citación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_agenda_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained('disciplinary_cases')->cascadeOnDelete();
            $table->foreignId('organizational_area_id')->nullable()->constrained('organizational_areas')->nullOnDelete();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('planning_replied_at')->nullable()->index();
            $table->timestamps();

            $table->unique('disciplinary_case_id');
        });

        Schema::create('disciplinary_agenda_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('disciplinary_agenda_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });

        Schema::create('disciplinary_agenda_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_message_id')->constrained('disciplinary_agenda_messages')->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path', 512);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_agenda_attachments');
        Schema::dropIfExists('disciplinary_agenda_messages');
        Schema::dropIfExists('disciplinary_agenda_threads');
    }
};
