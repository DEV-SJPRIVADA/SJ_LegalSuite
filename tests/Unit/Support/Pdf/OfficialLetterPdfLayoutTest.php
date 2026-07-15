<?php

namespace Tests\Unit\Support\Pdf;

use Tests\TestCase;

class OfficialLetterPdfLayoutTest extends TestCase
{
    public function test_shared_styles_use_fixed_letter_content_box(): void
    {
        $html = view('disciplinary.forms.partials.official-letter-pdf-styles')->render();

        $this->assertStringContainsString('@page { size: Letter; margin: 0; }', $html);
        $this->assertMatchesRegularExpression('/\.ogj-page\s*\{[^}]*width:\s*7\.5in;/s', $html);
        $this->assertMatchesRegularExpression('/\.ogj-page\s*\{[^}]*margin:\s*0\.5in;/s', $html);
        $this->assertMatchesRegularExpression('/\.ogj-page\s*\{[^}]*padding:\s*0;/s', $html);
        $this->assertDoesNotMatchRegularExpression('/\.ogj-03-signature-(block|slot-area)\s*\{[^}]*display:\s*flex/s', $html);
    }

    public function test_fo_gj_03_print_page_has_no_extra_padding_rule(): void
    {
        $blade = file_get_contents(resource_path('views/disciplinary/forms/partials/fo-gj-03-pdf-styles.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringNotContainsString('padding: 0.5in', $blade);
        $this->assertStringContainsString('ogj-page-break', $blade);
    }

    public function test_fo_gj_03_typical_html_has_header_on_closing_page(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-filled-download', [
            'fecha' => '15/07/2026',
            'caseNumber' => 'GJ-PD:000002',
            'workerName' => 'TEGUE LASPRILLA ABRAHAM',
            'workerDocument' => '123456789',
            'workerPosition' => 'GUARDA DE SEGURIDAD',
            'hearingDay' => '20/07/2026',
            'hearingTime' => '09:00 AM',
            'modality' => 'presencial',
            'locationText' => 'en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente',
            'informeReportDate' => '14/07/2026',
            'breachDate' => '08/07/2026',
            'chargesDescription' => 'Incumplimiento de obligaciones laborales según el informe; falta reiterada de presentación al puesto.',
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'signerName' => 'Abogado asignado',
            'signerRole' => 'Analista de Relaciones Laborales',
            'signatureDataUri' => null,
            'embeddedLogoSrc' => '',
        ])->render();

        $this->assertStringContainsString('Página 1 de 2', $html);
        $this->assertStringContainsString('Página 2 de 2', $html);
        $this->assertStringContainsString('ogj-page-break', $html);
        $this->assertSame(2, preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html));
        $this->assertStringContainsString('Cordialmente;', $html);
        $this->assertStringContainsString('elementos probatorios', $html);

        $posSecondHeader = strpos($html, 'Página 2 de 2');
        $posEvidence = strpos($html, 'elementos probatorios');
        $posClosing = strpos($html, 'Cordialmente;');
        $this->assertNotFalse($posSecondHeader);
        $this->assertNotFalse($posEvidence);
        $this->assertNotFalse($posClosing);
        $this->assertTrue($posEvidence > $posSecondHeader, 'evidence debe ir en la hoja 2 (tras su encabezado)');
        $this->assertTrue($posClosing > $posEvidence, 'cierre después del bloque evidence');
    }

    public function test_fo_gj_03_typical_dompdf_page_count_matches_header(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $html = view('disciplinary.forms.fo-gj-03-filled-download', [
            'fecha' => '15/07/2026',
            'caseNumber' => 'GJ-PD:000002',
            'workerName' => 'TEGUE LASPRILLA ABRAHAM',
            'workerDocument' => '123456789',
            'workerPosition' => 'GUARDA DE SEGURIDAD',
            'hearingDay' => '20/07/2026',
            'hearingTime' => '09:00 AM',
            'modality' => 'presencial',
            'locationText' => 'en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente',
            'informeReportDate' => '14/07/2026',
            'breachDate' => '08/07/2026',
            'chargesDescription' => 'Incumplimiento de obligaciones laborales según el informe; falta reiterada de presentación al puesto.',
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'signerName' => 'Abogado asignado',
            'signerRole' => 'Analista de Relaciones Laborales',
            'signatureDataUri' => null,
            'embeddedLogoSrc' => '',
        ])->render();

        $this->assertTrue((bool) preg_match('/Página 1 de (\d+)/', $html, $match));
        $plannedTotal = (int) $match[1];
        $this->assertSame(2, $plannedTotal);

        $binary = \App\Support\Pdf\HtmlLetterPdfGenerator::fromHtml($html);
        $this->assertStringStartsWith('%PDF', $binary);

        $physicalPages = preg_match_all('/\/Type\s*\/Page\b/', $binary);
        $this->assertSame(
            $plannedTotal,
            $physicalPages,
            'Dompdf generó páginas físicas distintas al encabezado (overflow sin header)',
        );
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
        $this->assertMatchesRegularExpression('/Página \d+ de \d+/', $html);
        $this->assertStringContainsString('width:25%', $html);
        $this->assertStringContainsString('width:50%', $html);
        $this->assertStringContainsString('Cordialmente;', $html);
        $this->assertStringContainsString('SJ Seguridad Privada Ltda', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertDoesNotMatchRegularExpression('/\.ogj-03-signature-block\s*\{[^}]*display:\s*flex/s', $html);
        $headerCount = preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html);
        $breakCount = preg_match_all('/\bogj-page-break\b/', $html) - preg_match_all('/\.ogj-page-break\b/', $html);
        $this->assertGreaterThanOrEqual(2, $headerCount);
        $this->assertSame($headerCount, $breakCount + 1);
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
