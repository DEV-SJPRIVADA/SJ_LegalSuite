<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IntegrationStatusCommand extends Command
{
    protected $signature = 'integracion:estado';

    protected $description = 'Muestra el ultimo lote de empleados recibido desde SJ StatFlow';

    public function handle(): int
    {
        $path = storage_path('app/integraciones/last-run.json');

        if (! is_file($path)) {
            $this->warn('Todavia no se ha recibido ningun lote de SJ StatFlow.');

            return self::SUCCESS;
        }

        $data = json_decode((string) File::get($path), true);

        if (! is_array($data)) {
            $this->error('El archivo de estado esta corrupto: '.$path);

            return self::FAILURE;
        }

        $this->info('Ultimo lote recibido desde SJ StatFlow');
        $this->table(['Campo', 'Valor'], [
            ['Recibido', (string) ($data['received_at'] ?? '-')],
            ['run_id', (string) ($data['run_id'] ?? '-')],
            ['Modo', (string) ($data['mode'] ?? '-')],
            ['Lote', trim((string) ($data['batch'] ?? '-').' / '.(string) ($data['total_batches'] ?? '-'), ' /')],
            ['Creados', (string) ($data['created'] ?? 0)],
            ['Actualizados', (string) ($data['updated'] ?? 0)],
            ['Sin cambios', (string) ($data['skipped'] ?? 0)],
            ['Errores', (string) count($data['errors'] ?? [])],
            ['IP origen', (string) ($data['remote_ip'] ?? '-')],
        ]);

        foreach (array_slice($data['errors'] ?? [], 0, 20) as $error) {
            $this->line(sprintf(' - %s: %s', $error['document_number'] ?? '?', $error['reason'] ?? '?'));
        }

        return self::SUCCESS;
    }
}
