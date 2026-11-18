<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integration\EmployeeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EmployeeIngestController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'ts' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request, EmployeeSyncService $sync): JsonResponse
    {
        $records = $request->input('records');

        if (! is_array($records)) {
            return response()->json([
                'ok' => false,
                'message' => 'El campo records debe ser un arreglo.',
            ], 422);
        }

        $summary = $sync->ingest($records);
        $summary['ok'] = $summary['errors'] === [];

        $this->recordLastRun($request, $summary);

        return response()->json($summary, $summary['errors'] === [] ? 200 : 207);
    }

    /**
     * Estado del ultimo lote recibido. Se guarda en archivo porque este repo
     * no admite tablas nuevas.
     *
     * @param  array<string, mixed>  $summary
     */
    private function recordLastRun(Request $request, array $summary): void
    {
        $payload = [
            'received_at' => now()->toIso8601String(),
            'run_id' => $request->input('run_id'),
            'mode' => $request->input('mode'),
            'batch' => $request->input('batch'),
            'total_batches' => $request->input('total_batches'),
            'created' => $summary['created'],
            'updated' => $summary['updated'],
            'skipped' => $summary['skipped'],
            'errors' => $summary['errors'],
            'remote_ip' => $request->ip(),
        ];

        $directory = storage_path('app/integraciones');
        File::ensureDirectoryExists($directory);
        File::put(
            $directory.'/last-run.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
    }
}
