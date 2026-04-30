<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal disciplinable (guardas, empleados operativos, etc.).
 *
 * Se almacena como entidad propia y NO como User para:
 *   - Desacoplar al disciplinado del sistema de autenticación
 *   - Soportar integraciones futuras con SJ_Armory u otros sistemas via external_id
 *   - Mantener historial aunque el empleado deje de existir
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 8)->default('CC');
            $table->string('document_number', 32)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 32)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('position', 120)->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('sede', 120)->nullable();
            $table->date('hired_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('external_id', 64)->nullable()->index()
                ->comment('ID en sistema externo (ej: SJ_Armory) para integraciones futuras');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
