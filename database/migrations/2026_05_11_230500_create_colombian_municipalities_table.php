<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colombian_municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('department_code', 2)->index();
            $table->string('department_name')->nullable();
            $table->string('municipality_code', 5)->unique();
            $table->string('municipality_name');
            $table->string('municipality_type')->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->decimal('latitude', 11, 7)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colombian_municipalities');
    }
};
