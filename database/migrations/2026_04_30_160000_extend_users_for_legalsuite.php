<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document_number', 32)->nullable()->after('email')->index();
            $table->string('phone', 32)->nullable()->after('document_number');
            $table->string('area', 32)->nullable()->after('phone')->index();
            $table->string('position', 120)->nullable()->after('area');
            $table->boolean('is_active')->default(true)->after('position')->index();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['document_number', 'phone', 'area', 'position', 'is_active']);
        });
    }
};
