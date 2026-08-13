<?php

namespace Tests\Unit\Support\Pdf;

use App\Support\Pdf\LetterPdfDriver;
use Tests\TestCase;

class LetterPdfDriverTest extends TestCase
{
    public function test_defaults_to_browsershot(): void
    {
        config(['services.pdf.driver' => null, 'services.pdf.use_queue' => false]);

        $this->assertSame(LetterPdfDriver::BROWSERSHOT, LetterPdfDriver::current());
    }

    public function test_dompdf_driver_detected(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $this->assertTrue(LetterPdfDriver::usesDompdf());
        $this->assertFalse(LetterPdfDriver::usesBrowsershot());
    }

    public function test_queue_disabled_when_dompdf(): void
    {
        config([
            'services.pdf.driver' => 'dompdf',
            'services.pdf.use_queue' => true,
        ]);

        $this->assertFalse(LetterPdfDriver::shouldUseQueue());
    }

    public function test_queue_requires_browsershot_and_flag(): void
    {
        config([
            'services.pdf.driver' => 'browsershot',
            'services.pdf.use_queue' => true,
        ]);

        // PHPUnit corre en CLI → shouldUseQueue es false (cola solo en web).
        $this->assertTrue(app()->runningInConsole());
        $this->assertFalse(LetterPdfDriver::shouldUseQueue());
    }
}
