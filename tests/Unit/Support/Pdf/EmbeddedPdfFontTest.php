<?php

namespace Tests\Unit\Support\Pdf;

use App\Support\Pdf\EmbeddedPdfFont;
use Tests\TestCase;

class EmbeddedPdfFontTest extends TestCase
{
    public function test_required_liberation_files_are_present(): void
    {
        $this->assertSame([], EmbeddedPdfFont::missingFiles());
    }

    public function test_sans_font_face_css_embeds_ttf_data_uris(): void
    {
        $css = EmbeddedPdfFont::sansFontFaceCss();

        $this->assertStringContainsString('@font-face', $css);
        $this->assertStringContainsString('font-family:'.EmbeddedPdfFont::FAMILY_SANS, $css);
        $this->assertStringContainsString("data:font/ttf;base64,", $css);
        $this->assertStringContainsString('font-weight:400', $css);
        $this->assertStringContainsString('font-weight:700', $css);
    }

    public function test_serif_font_face_css_embeds_ttf_data_uris(): void
    {
        $css = EmbeddedPdfFont::serifFontFaceCss();

        $this->assertStringContainsString('font-family:'.EmbeddedPdfFont::FAMILY_SERIF, $css);
        $this->assertStringContainsString("data:font/ttf;base64,", $css);
    }

    public function test_official_letter_styles_use_embedded_sans_family(): void
    {
        $html = view('disciplinary.forms.partials.official-letter-pdf-styles')->render();

        $this->assertStringContainsString(EmbeddedPdfFont::FAMILY_SANS, $html);
        $this->assertStringContainsString('@font-face', $html);
        $this->assertStringContainsString('data:font/ttf;base64,', $html);
    }
}
