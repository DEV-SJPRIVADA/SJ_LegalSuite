<?php

namespace Tests\Unit\Support\LegalDocuments;

use App\Support\LegalDocuments\LegalDocumentFrequencyParser;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LegalDocumentFrequencyParserTest extends TestCase
{
    public function test_mensual_avanza_un_mes(): void
    {
        $from = Carbon::parse('2026-01-31');
        $next = LegalDocumentFrequencyParser::nextRenewOn('MENSUAL', $from);

        $this->assertNotNull($next);
        $this->assertSame('2026-02-28', $next->toDateString());
    }

    public function test_seis_meses(): void
    {
        $from = Carbon::parse('2026-03-15');
        $next = LegalDocumentFrequencyParser::nextRenewOn('6 MESES', $from);

        $this->assertNotNull($next);
        $this->assertSame('2026-09-15', $next->toDateString());
    }

    public function test_desconocida_devuelve_null(): void
    {
        $this->assertNull(LegalDocumentFrequencyParser::nextRenewOn('cuando se pueda', now()));
    }
}
