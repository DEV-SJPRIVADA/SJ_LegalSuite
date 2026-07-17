<?php

namespace Tests\Unit\Support\Pdf;

use App\Support\Pdf\HtmlLetterPdfGenerator;
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

    public function test_fo_gj_03_styles_use_explicit_pages_without_fixed_letterhead(): void
    {
        $blade = file_get_contents(resource_path('views/disciplinary/forms/partials/fo-gj-03-pdf-styles.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('@page { size: Letter; margin: 0; }', $blade);
        $this->assertStringContainsString('ogj-page-break', $blade);
        $this->assertStringNotContainsString('position: fixed', $blade);
        $this->assertStringContainsString('height: 76px', $blade);
        $this->assertMatchesRegularExpression(
            '/\.ogj-03-closing-block\s*\{[^}]*page-break-inside:\s*avoid;/s',
            $blade,
        );
    }

    public function test_fo_gj_03_html_uses_explicit_pages_with_header_each(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-filled-download', $this->typicalFoGj03ViewData())->render();

        $this->assertStringNotContainsString('data-sj-pdf-flow="fo-gj-03"', $html);
        $this->assertStringContainsString('ogj-03-flow', $html);
        $this->assertStringContainsString('ogj-03-closing-block', $html);
        $this->assertStringContainsString('width:25%', $html);
        $this->assertStringContainsString('width:50%', $html);
        $this->assertStringContainsString('Cordialmente;', $html);
        $this->assertStringContainsString('elementos probatorios', $html);
        $this->assertStringContainsString('SJ Seguridad Privada Ltda', $html);
        $this->assertStringContainsString('Página 1 de 1', $html);
        $this->assertSame(1, preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html));
        $this->assertDoesNotMatchRegularExpression('/\.ogj-03-signature-block\s*\{[^}]*display:\s*flex/s', $html);
    }

    public function test_fo_gj_03_canonical_dompdf_fits_one_physical_page(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $html = view('disciplinary.forms.fo-gj-03-filled-download', $this->typicalFoGj03ViewData())->render();
        $binary = HtmlLetterPdfGenerator::fromHtml($html);

        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertSame(
            1,
            preg_match_all('/\/Type\s*\/Page\b/', $binary),
            'La forma canónica FO-GJ-03 debe caber en 1 página Letter',
        );
        $this->assertSame(1, $this->countPdfStreamNeedle($binary, 'Página 1 de 1'));
    }

    public function test_fo_gj_03_long_charges_paginate_with_header_on_each_html_page(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $data = $this->typicalFoGj03ViewData();
        $data['chargesDescription'] = str_repeat('Descripción extendida del cargo disciplinario. ', 80);

        $html = view('disciplinary.forms.fo-gj-03-filled-download', $data)->render();
        $binary = HtmlLetterPdfGenerator::fromHtml($html);

        $this->assertStringStartsWith('%PDF', $binary);
        $plannedHeaders = preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html);
        $this->assertGreaterThanOrEqual(2, $plannedHeaders);
        $this->assertStringContainsString('ogj-page-break', $html);
        $this->assertMatchesRegularExpression('/Página 1 de \d+/', $html);
        $this->assertMatchesRegularExpression('/continuación/', $html);

        $physicalPages = preg_match_all('/\/Type\s*\/Page\b/', $binary);
        $this->assertGreaterThanOrEqual(2, $physicalPages);
        // Ideal: 1 HTML page ≈ 1 física. Holgura: no más físicas que planificadas + 1.
        $this->assertLessThanOrEqual($plannedHeaders + 1, $physicalPages);
        $this->assertSame(
            $plannedHeaders,
            $this->countPdfStreamNeedle($binary, 'FO-GJ-03'),
            'Cada página planificada debe llevar letterhead FO-GJ-03 en el PDF',
        );

        // Firmas atómicas en HTML: cada closing-block trae Cordialmente + nombres juntos.
        $this->assertSame(1, preg_match_all('/class="ogj-03-closing-block"/', $html));
        $this->assertMatchesRegularExpression(
            '/class="ogj-03-closing-block"[\s\S]*Cordialmente;[\s\S]*Nombre:[\s\S]*SJ Seguridad Privada Ltda/u',
            $html,
        );
    }

    public function test_fo_gj_03_full_body_keeps_signatures_block_intact_in_pdf(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $data = $this->typicalFoGj03ViewData();
        // Cuerpo largo: firmas deben quedar en su propia .ogj-page (enteras), no partidas.
        $data['chargesDescription'] = str_repeat('Cargo disciplinario con detalle suficiente para llenar hojas. ', 120);

        $html = view('disciplinary.forms.fo-gj-03-filled-download', $data)->render();
        $binary = HtmlLetterPdfGenerator::fromHtml($html);

        $this->assertGreaterThanOrEqual(2, preg_match_all('/\/Type\s*\/Page\b/', $binary));
        $this->assertSame(1, preg_match_all('/class="ogj-03-closing-block"/', $html));
        $this->assertSame(
            1,
            $this->countPdfStreamNeedle($binary, 'Cordialmente'),
            'Cordialmente debe aparecer una sola vez (bloque de firmas no duplicado/partido)',
        );
        $this->assertGreaterThanOrEqual(
            1,
            $this->countPdfStreamNeedle($binary, 'SJ Seguridad Privada Ltda'),
        );
        // Misma cantidad de headers planificados que menciones FO-GJ-03 en streams.
        $plannedHeaders = preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html);
        $this->assertSame($plannedHeaders, $this->countPdfStreamNeedle($binary, 'FO-GJ-03'));
    }

    public function test_fo_gj_03_blank_download_uses_explicit_single_page(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-blank-download', [
            'embeddedLogoSrc' => '',
        ])->render();

        $this->assertStringNotContainsString('data-sj-pdf-flow="fo-gj-03"', $html);
        $this->assertSame(1, preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html));
        $this->assertStringContainsString('Página 1 de 1', $html);
        $this->assertStringContainsString('height: 76px', $html);
        $this->assertStringNotContainsString('1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42', $html);
        $this->assertStringNotContainsString('10, 34', $html);
        $this->assertStringNotContainsString('3, 12, 15, 22, 25, 36, 64, 98, 103, 112', $html);
        $this->assertStringContainsString('Artículo 66, numeral', $html);
        $this->assertStringContainsString('ogj-03-guide', $html);
    }

    public function test_fo_gj_03_filled_download_head_does_not_duplicate_legacy_page_rule(): void
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

    /**
     * @return array<string, mixed>
     */
    private function typicalFoGj03ViewData(): array
    {
        return [
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
        ];
    }

    private function countPdfStreamNeedle(string $binary, string $needle): int
    {
        $count = 0;
        $needleUtf16 = mb_convert_encoding($needle, 'UTF-16BE', 'UTF-8');

        if (! preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $binary, $matches)) {
            return 0;
        }

        foreach ($matches[1] as $raw) {
            $decoded = @gzuncompress($raw);
            if ($decoded === false) {
                $decoded = @gzinflate(substr($raw, 2));
            }

            if (! is_string($decoded)) {
                continue;
            }

            if (str_contains($decoded, $needle)) {
                $count++;

                continue;
            }

            if (is_string($needleUtf16) && $needleUtf16 !== '' && str_contains($decoded, $needleUtf16)) {
                $count++;
            }
        }

        return $count;
    }
}
