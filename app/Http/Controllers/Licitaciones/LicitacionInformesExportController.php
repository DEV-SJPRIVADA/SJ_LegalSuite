<?php

namespace App\Http\Controllers\Licitaciones;

use App\Http\Controllers\Controller;
use App\Models\Licitaciones\Licitacion;
use App\Services\Licitaciones\LicitacionInformesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicitacionInformesExportController extends Controller
{
    public function __invoke(Request $request, LicitacionInformesService $informes): StreamedResponse
    {
        Gate::authorize('viewDashboard', Licitacion::class);

        $filters = [
            'fecha_desde' => $request->string('fecha_desde')->toString() ?: null,
            'fecha_hasta' => $request->string('fecha_hasta')->toString() ?: null,
            'estado_proceso' => $request->string('estado_proceso')->toString() ?: null,
            'cumplimos' => $request->string('cumplimos')->toString() ?: null,
        ];

        $rows = $informes->licitacionesForReport($filters);

        return response()->streamDownload(function () use ($informes, $rows): void {
            $tmp = tempnam(sys_get_temp_dir(), 'lic_inf_');
            if ($tmp === false) {
                abort(500);
            }
            $path = $tmp.'.xlsx';
            rename($tmp, $path);
            try {
                $informes->writeExcelToPath($rows, $path);
                echo file_get_contents($path);
            } finally {
                @unlink($path);
            }
        }, 'informe-licitaciones-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
