<?php

namespace Tests\Unit\Support\Pdf;

use App\Support\Pdf\DompdfLetterPdfDriver;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Tests\TestCase;

class DompdfLetterPdfDriverTest extends TestCase
{
    public function test_dompdf_renders_non_empty_pdf_with_latin_text(): void
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head>'
            .'<body class="ogj-wrap"><p>SJ LegalSuite FO-GJ-03 ñáéíóú</p></body></html>';

        $binary = DompdfLetterPdfDriver::render($html);

        $this->assertNotSame('', $binary);
        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertGreaterThan(500, strlen($binary));
    }

    public function test_facade_uses_dompdf_when_configured(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $binary = HtmlLetterPdfGenerator::fromHtml(
            '<!DOCTYPE html><html><body><p>Driver facade</p></body></html>',
        );

        $this->assertStringStartsWith('%PDF', $binary);
    }
}
