<?php

namespace Tests\Unit\Jobs\Disciplinary;

use App\Jobs\Disciplinary\ProcessFoGj51PdfJob;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessFoGj51PdfJobQueueTest extends TestCase
{
    public function test_job_is_dispatched_on_dedicated_pdf_queue(): void
    {
        Bus::fake();

        ProcessFoGj51PdfJob::dispatch('token-test');

        Bus::assertDispatched(ProcessFoGj51PdfJob::class, function (ProcessFoGj51PdfJob $job): bool {
            return $job->queue === ProcessFoGj51PdfJob::QUEUE
                && $job->token === 'token-test';
        });
    }
}
