<?php

namespace Tests\Unit\Support\Pdf;

use App\Support\Pdf\HtmlLetterPdfGenerator;
use Tests\TestCase;

class HtmlLetterPdfGeneratorArtisanCliTest extends TestCase
{
    public function test_web_request_delegates_when_use_queue_enabled(): void
    {
        config([
            'services.pdf.via_artisan_cli' => false,
            'services.pdf.use_queue' => true,
        ]);

        $this->assertTrue(HtmlLetterPdfGenerator::shouldDelegateToArtisanCli(runningInConsole: false));
    }

    public function test_web_request_delegates_when_via_artisan_cli_flag(): void
    {
        config([
            'services.pdf.via_artisan_cli' => true,
            'services.pdf.use_queue' => false,
        ]);

        $this->assertTrue(HtmlLetterPdfGenerator::shouldDelegateToArtisanCli(runningInConsole: false));
    }

    public function test_console_never_delegates_to_avoid_recursion(): void
    {
        config([
            'services.pdf.via_artisan_cli' => true,
            'services.pdf.use_queue' => true,
        ]);

        $this->assertFalse(HtmlLetterPdfGenerator::shouldDelegateToArtisanCli(runningInConsole: true));
    }

    public function test_local_web_defaults_do_not_delegate(): void
    {
        config([
            'services.pdf.via_artisan_cli' => false,
            'services.pdf.use_queue' => false,
        ]);

        $this->assertFalse(HtmlLetterPdfGenerator::shouldDelegateToArtisanCli(runningInConsole: false));
    }
}
