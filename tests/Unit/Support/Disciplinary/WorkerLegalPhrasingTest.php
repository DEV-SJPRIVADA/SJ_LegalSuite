<?php

namespace Tests\Unit\Support\Disciplinary;

use App\Enums\EmployeeGender;
use App\Support\Disciplinary\WorkerLegalPhrasing;
use Tests\TestCase;

class WorkerLegalPhrasingTest extends TestCase
{
    public function test_masculine_fo_gj_03_phrases(): void
    {
        $phrasing = WorkerLegalPhrasing::fromGender(EmployeeGender::Masculino);

        $this->assertTrue($phrasing->hasDefiniteGender());
        $this->assertSame('Respetado trabajador;', $phrasing->foGj03OpeningSalutation());
        $this->assertSame('citarlo', $phrasing->foGj03CitationVerb());
        $this->assertSame('ser escuchado', $phrasing->foGj03DefenseHearingPhrase());
        $this->assertStringContainsString('al trabajador', $phrasing->foGj03EvidenceTrasladoText());
    }

    public function test_feminine_fo_gj_03_phrases(): void
    {
        $phrasing = WorkerLegalPhrasing::fromGender(EmployeeGender::Femenino);

        $this->assertTrue($phrasing->hasDefiniteGender());
        $this->assertSame('Respetada trabajadora;', $phrasing->foGj03OpeningSalutation());
        $this->assertSame('citarla', $phrasing->foGj03CitationVerb());
        $this->assertSame('ser escuchada', $phrasing->foGj03DefenseHearingPhrase());
        $this->assertStringContainsString('a la trabajadora', $phrasing->foGj03EvidenceTrasladoText());
    }

    public function test_neutral_when_gender_is_not_indicated(): void
    {
        $phrasing = WorkerLegalPhrasing::fromGender(EmployeeGender::NoIndica);

        $this->assertFalse($phrasing->hasDefiniteGender());
        $this->assertSame('Cordial saludo;', $phrasing->foGj03OpeningSalutation());
        $this->assertSame('citarle', $phrasing->foGj03CitationVerb());
        $this->assertStringContainsString('Se le corre traslado', $phrasing->foGj03EvidenceTrasladoText());
    }

    public function test_fo_gj_04_party_and_signature_labels(): void
    {
        $male = WorkerLegalPhrasing::fromGender(EmployeeGender::Masculino);
        $female = WorkerLegalPhrasing::fromGender(EmployeeGender::Femenino);

        $this->assertSame('EL TRABAJADOR:', $male->foGj04PartyLabel());
        $this->assertSame('LA TRABAJADORA:', $female->foGj04PartyLabel());
        $this->assertSame('TRABAJADOR,', $male->foGj04SignatureLabel());
        $this->assertSame('TRABAJADORA,', $female->foGj04SignatureLabel());
        $this->assertStringContainsString('del trabajador', $male->foGj04FreeVersionPhrase());
        $this->assertStringContainsString('de la trabajadora', $female->foGj04FreeVersionPhrase());
    }

    public function test_fo_gj_54_scheduled_hearing_phrase(): void
    {
        $male = WorkerLegalPhrasing::fromGender(EmployeeGender::Masculino);
        $female = WorkerLegalPhrasing::fromGender(EmployeeGender::Femenino);

        $this->assertStringContainsString('citado usted', $male->foGj54ScheduledHearingPhrase());
        $this->assertStringContainsString('citada usted', $female->foGj54ScheduledHearingPhrase());
    }
}
