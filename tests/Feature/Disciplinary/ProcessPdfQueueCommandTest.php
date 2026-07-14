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
        config(['services.pdf.use_queue' => false]);

        $this->artisan('disciplinary:process-pdf-queue')
            ->expectsOutputToContain('PDF_USE_QUEUE está inactivo')
            ->assertSuccessful();
    }
}
