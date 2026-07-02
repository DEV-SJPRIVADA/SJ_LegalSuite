<?php

namespace App\Console\Commands;

use App\Services\Licitaciones\LicitacionFixedSolicitudResetService;
use Illuminate\Console\Command;

class ResetFixedLicitacionSolicitudes extends Command
{
    protected $signature = 'licitaciones:reset-fixed-solicitudes';

    protected $description = 'Reinicia solicitudes fijas respondidas cuya fecha límite ya venció';

    public function handle(LicitacionFixedSolicitudResetService $service): int
    {
        $result = $service->resetExpired();

        $this->info("Solicitudes reiniciadas: {$result['processed']}");

        if ($result['processed'] > 0) {
            $this->line('IDs: '.implode(', ', $result['ids']));
        }

        return self::SUCCESS;
    }
}
