<?php

namespace Tests\Unit\Support\Pdf;

use Tests\TestCase;

class OfficialLetterPdfLayoutTest extends TestCase
{
    public function test_shared_styles_use_hybrid_page_margins(): void
    {
        $html = view('disciplinary.forms.partials.official-letter-pdf-styles')->render();

        $this->assertStringContainsString('@page { size: Letter; margin: 0.5in 0; }', $html);
        $this->assertMatchesRegularExpression('/\.ogj-page\s*\{[^}]*padding:\s*0\s+0\.5in;/s', $html);
        $this->assertDoesNotMatchRegularExpression('/\.ogj-03-signature-(block|slot-area)\s*\{[^}]*display:\s*flex/s', $html);
    }

    public function test_fo_gj_03_styles_use_full_page_padding(): void
    {
        $html = view('disciplinary.forms.partials.fo-gj-03-pdf-styles')->render();

        $this->assertStringContainsString('@page { size: Letter; margin: 0; }', $html);
        $this->assertMatchesRegularExpression('/\.ogj-page\s*\{[^}]*padding:\s*0\.5in;/s', $html);
    }

    public function test_fo_gj_03_filled_html_uses_planner_pages_and_closing_block(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-filled-download', [
            'fecha' => '15 de julio de 2026',
            'caseNumber' => 'GJ-PD:000002',
            'workerName' => 'TEGUE LASPRILLA ABRAHAM',
            'workerDocument' => '123456789',
            'workerPosition' => 'GUARDA DE SEGURIDAD',
            'hearingDay' => '20 de julio de 2026',
            'hearingTime' => '09:00 a.m.',
            'modality' => 'presencial',
            'locationText' => 'Av. 4 Nte. #26N - 39 B/ San Vicente',
            'informeReportDate' => '10 de julio de 2026',
            'breachDate' => '8 de julio de 2026',
            'chargesDescription' => str_repeat('Descripción extendida del cargo disciplinario. ', 35),
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'signerName' => 'Analista Demo',
            'signerRole' => 'Analista de Relaciones Laborales',
            'signatureDataUri' => null,
            'embeddedLogoSrc' => '',
        ])->render();

        $this->assertStringContainsString('ogj-03-closing-block', $html);
        $this->assertStringContainsString('ogj-page-break', $html);
        $this->assertStringContainsString('Página 2 de 2', $html);
        $this->assertStringContainsString('Cordialmente;', $html);
        $this->assertStringContainsString('SJ Seguridad Privada Ltda', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertDoesNotMatchRegularExpression('/\.ogj-03-signature-block\s*\{[^}]*display:\s*flex/s', $html);
    }

    public function test_fo_gj_03_filled_download_head_does_not_duplicate_page_rule(): void
    {
        $blade = file_get_contents(resource_path('views/disciplinary/forms/fo-gj-03-filled-download.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringNotContainsString('@page { size: Letter; margin: 0.45in; }', $blade);
        $this->assertStringContainsString('fo-gj-03-pdf-styles', $blade);
    }

    public function test_sibling_letter_downloads_do_not_duplicate_page_rule_in_head(): void
    {
        $views = [
            'fo-gj-44-filled-download',
            'fo-gj-54-filled-download',
            'fo-gj-51-filled-download',
            'decision-comunicado-filled-download',
        ];

        foreach ($views as $view) {
            $blade = file_get_contents(resource_path('views/disciplinary/forms/'.$view.'.blade.php'));
            $this->assertIsString($blade, $view);
            $this->assertStringNotContainsString(
                '@page { size: Letter; margin: 0.45in; }',
                $blade,
                $view.' still declares duplicate @page in head',
            );
        }
    }
}
