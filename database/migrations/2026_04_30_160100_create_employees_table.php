<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('document_type', 8)->default('CC');
            $table->string('document_number', 32)->unique();
            $table->date('birth_date')->nullable();
            $table->string('gender', 24)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('municipality_code', 5)->nullable()->index();
            $table->string('phone', 32)->nullable();
            $table->string('email', 150)->nullable();
            $table->date('hired_at')->nullable();
            $table->string('contract_type', 32)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('department_area', 120)->nullable();
            $table->decimal('base_salary', 14, 2)->nullable();
            $table->date('termination_at')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 32)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('external_id', 64)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
