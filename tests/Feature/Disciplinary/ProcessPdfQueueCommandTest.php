<?php

namespace Tests\Feature\Disciplinary;

use Tests\TestCase;

class ProcessPdfQueueCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->artisan('disciplinary:process-pdf-queue --help')
            ->assertSuccessful();
    }

    public function test_command_exits_cleanly_when_queue_disabled(): void
    {
        config([
            'services.pdf.driver' => 'browsershot',
            'services.pdf.use_queue' => false,
        ]);

        $this->artisan('disciplinary:process-pdf-queue')
            ->expectsOutputToContain('PDF_USE_QUEUE está inactivo')
            ->assertSuccessful();
    }

    public function test_command_skips_when_dompdf_driver(): void
    {
        config([
            'services.pdf.driver' => 'dompdf',
            'services.pdf.use_queue' => true,
        ]);

        $this->artisan('disciplinary:process-pdf-queue')
            ->expectsOutputToContain('PDF_DRIVER=dompdf')
            ->assertSuccessful();
    }
}
