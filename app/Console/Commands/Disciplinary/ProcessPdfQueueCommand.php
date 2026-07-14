<?php

namespace App\Console\Commands\Disciplinary;

use App\Support\Pdf\LetterPdfDriver;
use Illuminate\Console\Command;

/**
 * Drena la cola PDF en hosting (cron). Solo relevante con PDF_DRIVER=browsershot.
 */
class ProcessPdfQueueCommand extends Command
{
    protected $signature = 'disciplinary:process-pdf-queue
                            {--max-time=55 : Segundos máximos del worker}';

    protected $description = 'Procesa jobs de colas pdf,default (FO-GJ-51/03 y notificaciones)';

    public function handle(): int
    {
        if (LetterPdfDriver::usesDompdf()) {
            $this->info('PDF_DRIVER=dompdf: no se usa cola Browsershot (generación síncrona).');

            return self::SUCCESS;
        }

        if (! config('services.pdf.use_queue')) {
            $this->warn('PDF_USE_QUEUE está inactivo; no hay nada que drenar.');

            return self::SUCCESS;
        }

        return $this->call('queue:work', [
            'connection' => 'database',
            '--queue' => 'pdf,default',
            '--stop-when-empty' => true,
            '--max-time' => (int) $this->option('max-time'),
            '--tries' => 1,
        ]);
    }
}
