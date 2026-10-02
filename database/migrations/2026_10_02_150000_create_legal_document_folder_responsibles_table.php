<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_folder_responsibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('legal_document_folders')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['folder_id', 'email']);
            $table->index(['folder_id', 'is_primary']);
        });

        // Migrar director actual (responsible_email / responsible_user_id) como principal.
        $folders = DB::table('legal_document_folders')
            ->select(['id', 'responsible_email', 'responsible_user_id'])
            ->get();

        $now = now();
        foreach ($folders as $folder) {
            $email = strtolower(trim((string) ($folder->responsible_email ?? '')));
            if ($email === '' && $folder->responsible_user_id) {
                $email = strtolower(trim((string) (DB::table('users')->where('id', $folder->responsible_user_id)->value('email') ?? '')));
            }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $name = (string) (DB::table('directory_users')->whereRaw('LOWER(email) = ?', [$email])->value('name') ?? '');
            if ($name === '') {
                $name = (string) (DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->value('name') ?? '');
            }

            DB::table('legal_document_folder_responsibles')->insert([
                'folder_id' => $folder->id,
                'email' => $email,
                'name' => $name !== '' ? $name : null,
                'user_id' => $folder->responsible_user_id ?: null,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_folder_responsibles');
    }
};
