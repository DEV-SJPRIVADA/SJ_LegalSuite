<?php

namespace Tests\Unit\Support\Pdf;

use Tests\TestCase;

class OfficialLetterPdfLayoutTest extends TestCase
{
    public function test_shared_styles_use_page_margins_without_ogj_page_padding(): void
    {
        $html = view('disciplinary.forms.partials.official-letter-pdf-styles')->render();

        $this->assertStringContainsString('@page { size: Letter; margin: 0.45in; }', $html);
        $this->assertMatchesRegularExpression('/\.ogj-page\s*\{[^}]*padding:\s*0;/s', $html);
        $this->assertStringContainsString('.ogj-03-closing-block', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertStringContainsString('break-inside: avoid', $html);
    }

    public function test_fo_gj_03_filled_html_keeps_closing_block_together(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-filled-download', [
            'fecha' => '15 de julio de 2026',
            'caseNumber' => 'DC-2026-0001',
            'workerName' => 'Juan Pérez',
            'workerDocument' => '123456789',
            'workerPosition' => 'Vigilante',
            'hearingDay' => '20 de julio de 2026',
            'hearingTime' => '09:00 a.m.',
            'modality' => 'presencial',
            'locationText' => 'Sede principal',
            'informeReportDate' => '10 de julio de 2026',
            'breachDate' => '8 de julio de 2026',
            'chargesDescription' => 'Incumplimiento de turno.',
            'article66Numerals' => '1',
            'article68Numerals' => '',
            'article76Numerals' => '',
            'signerName' => 'Analista Demo',
            'signerRole' => 'Analista de Relaciones Laborales',
            'signatureDataUri' => null,
            'embeddedLogoSrc' => null,
        ])->render();

        $this->assertStringContainsString('ogj-03-closing-block', $html);
        $this->assertStringContainsString('Cordialmente;', $html);
        $this->assertStringContainsString('SJ Seguridad Privada Ltda', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
    }

    public function test_fo_gj_03_filled_download_head_does_not_duplicate_page_rule(): void
    {
        $blade = file_get_contents(resource_path('views/disciplinary/forms/fo-gj-03-filled-download.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringNotContainsString('@page { size: Letter; margin: 0.45in; }', $blade);
    }
}
