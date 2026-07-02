<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitaciones', function (Blueprint $table) {
            $table->string('adjudicado')->nullable()->after('estado_proceso');
            $table->text('motivo_perdida')->nullable()->after('adjudicado');
        });

        $rows = DB::table('licitaciones')
            ->whereNull('adjudicado')
            ->whereNotNull('resultado')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
                $resultado = strtolower((string) $row->resultado);
                $adjudicado = null;
                $motivo = null;

                if (str_contains($resultado, 'si')) {
                    $adjudicado = 'Si';
                } elseif (str_contains($resultado, 'no')) {
                    $adjudicado = 'No';
                    $motivo = $row->resultado;
                }

                if ($adjudicado !== null) {
                    DB::table('licitaciones')->where('id', $row->id)->update([
                        'adjudicado' => $adjudicado,
                        'motivo_perdida' => $motivo,
                    ]);
                }
        }
    }

    public function down(): void
    {
        Schema::table('licitaciones', function (Blueprint $table) {
            $table->dropColumn(['adjudicado', 'motivo_perdida']);
        });
    }
};
