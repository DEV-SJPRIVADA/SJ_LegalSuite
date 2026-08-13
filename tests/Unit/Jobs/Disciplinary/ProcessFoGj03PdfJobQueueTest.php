<?php

namespace Tests\Unit\Jobs\Disciplinary;

use App\Jobs\Disciplinary\ProcessFoGj03PdfJob;
use App\Jobs\Disciplinary\ProcessFoGj51PdfJob;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessFoGj03PdfJobQueueTest extends TestCase
{
    public function test_job_is_dispatched_on_pdf_queue(): void
    {
        Bus::fake();

        ProcessFoGj03PdfJob::dispatch('aabbccddeeff00112233445566778899');

        Bus::assertDispatched(ProcessFoGj03PdfJob::class, function (ProcessFoGj03PdfJob $job): bool {
            return $job->queue === ProcessFoGj51PdfJob::QUEUE;
        });
    }
}
