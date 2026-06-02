<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mensajes de chat informal guardados como lawyer_request bloqueaban el flujo B.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('disciplinary_agenda_messages')
            ->where('message_kind', 'lawyer_request')
            ->where('body', 'not like', 'Solicitud de programación de fechas%')
            ->update(['message_kind' => 'general']);
    }

    public function down(): void
    {
        //
    }
};
