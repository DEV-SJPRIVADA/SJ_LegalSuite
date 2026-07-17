<?php

namespace Tests\Unit\Support\Pdf;

use App\Support\Pdf\DompdfLetterPdfDriver;
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

    public function test_fo_gj_03_styles_use_continuous_flow_and_fixed_letterhead(): void
    {
        $blade = file_get_contents(resource_path('views/disciplinary/forms/partials/fo-gj-03-pdf-styles.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('@page { size: Letter; margin: 0; }', $blade);
        $this->assertStringContainsString('position: fixed', $blade);
        $this->assertStringContainsString('ogj-03-letterhead', $blade);
        $this->assertMatchesRegularExpression(
            '/\.ogj-page\.ogj-03-page\s*\{[^}]*width:\s*7\.5in;[^}]*margin:\s*0\.5in;/s',
            $blade,
        );
        $this->assertMatchesRegularExpression(
            '/\.ogj-03-letterhead\s*\{[^}]*top:\s*0\.5in;[^}]*left:\s*0\.5in;[^}]*width:\s*7\.5in;/s',
            $blade,
        );
        $this->assertStringContainsString('height: 76px', $blade);
        $this->assertStringNotContainsString('ogj-page-break', $blade);
    }

    public function test_fo_gj_03_html_is_continuous_with_single_letterhead(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-filled-download', $this->typicalFoGj03ViewData())->render();

        $this->assertStringContainsString('data-sj-pdf-flow="fo-gj-03"', $html);
        $this->assertStringContainsString('ogj-03-letterhead', $html);
        $this->assertStringContainsString('ogj-03-flow', $html);
        $this->assertStringContainsString('ogj-03-closing-block', $html);
        $this->assertStringContainsString('width:25%', $html);
        $this->assertStringContainsString('width:50%', $html);
        $this->assertStringContainsString('Cordialmente;', $html);
        $this->assertStringContainsString('elementos probatorios', $html);
        $this->assertStringContainsString('SJ Seguridad Privada Ltda', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertStringNotContainsString('ogj-page-break', $html);
        $this->assertTrue(DompdfLetterPdfDriver::isFoGj03ContinuousFlow($html));
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

    public function test_fo_gj_03_long_charges_paginate_without_orphan_pages(): void
    {
        config(['services.pdf.driver' => 'dompdf']);

        $data = $this->typicalFoGj03ViewData();
        $data['chargesDescription'] = str_repeat('Descripción extendida del cargo disciplinario. ', 80);

        $html = view('disciplinary.forms.fo-gj-03-filled-download', $data)->render();
        $binary = HtmlLetterPdfGenerator::fromHtml($html);

        $this->assertStringStartsWith('%PDF', $binary);
        $physicalPages = preg_match_all('/\/Type\s*\/Page\b/', $binary);
        $this->assertGreaterThanOrEqual(2, $physicalPages);
        $this->assertSame(1, preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html));
        $this->assertTrue(DompdfLetterPdfDriver::isFoGj03ContinuousFlow($html));

        $this->assertSame(
            1,
            $this->countPdfStreamNeedle($binary, 'Página 1 de '.$physicalPages),
            'Falta “Página 1 de N” en la primera página física',
        );
        $this->assertSame(
            1,
            $this->countPdfStreamNeedle($binary, 'Página '.$physicalPages.' de '.$physicalPages),
            'Falta “Página N de N” en la última página física',
        );
        // “Página” vía canvas aparece en cada página física ( Dompdf fixed letterhead + page_text ).
        $this->assertSame(
            $physicalPages,
            $this->countPdfStreamNeedle($binary, 'Página'),
            'Cada página física debe llevar numeración Página N de M',
        );
    }

    public function test_fo_gj_03_blank_download_also_uses_continuous_flow(): void
    {
        $html = view('disciplinary.forms.fo-gj-03-blank-download', [
            'embeddedLogoSrc' => '',
        ])->render();

        $this->assertStringContainsString('data-sj-pdf-flow="fo-gj-03"', $html);
        $this->assertSame(1, preg_match_all('/<td class="ogj-meta-code">FO-GJ-03<\/td>/', $html));
        $this->assertStringContainsString('padding-top: 94px', $html);
        $this->assertMatchesRegularExpression('/\.ogj-03-flow p\s*\{[^}]*text-align:\s*justify;/s', $html);
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
