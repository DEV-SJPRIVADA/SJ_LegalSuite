<?php

namespace Tests\Unit\Support\Disciplinary;

use App\Enums\EmployeeGender;
use App\Support\Disciplinary\FoGj46HearingLead;
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

        $this->assertStringContainsString('citado para ser escuchado', $male->foGj54ScheduledHearingPhrase());
        $this->assertStringContainsString('citada para ser escuchada', $female->foGj54ScheduledHearingPhrase());
        $this->assertSame('Respetado colaborador.', $male->foGj54OpeningSalutation());
        $this->assertSame('Respetada colaboradora.', $female->foGj54OpeningSalutation());
    }

    public function test_fo_gj_46_gender_concordance(): void
    {
        $male = WorkerLegalPhrasing::fromGender(EmployeeGender::Masculino);
        $female = WorkerLegalPhrasing::fromGender(EmployeeGender::Femenino);
        $neutral = WorkerLegalPhrasing::fromGender(EmployeeGender::NoIndica);

        $this->assertSame(
            'usted fue citado a una',
            $male->foGj46HearingLeadPhrase(FoGj46HearingLead::Citado),
        );
        $this->assertSame(
            'usted fue citada a una',
            $female->foGj46HearingLeadPhrase(FoGj46HearingLead::Citado),
        );
        $this->assertSame(
            'usted fue citado(a) a una',
            $neutral->foGj46HearingLeadPhrase(FoGj46HearingLead::Citado),
        );
        $this->assertSame('y una vez surtida la', $female->foGj46HearingLeadPhrase(FoGj46HearingLead::Surtida));

        $surtidaBridge = $male->foGj46PostHearingBridge(FoGj46HearingLead::Surtida);
        $citadoBridge = $male->foGj46PostHearingBridge(FoGj46HearingLead::Citado);
        $this->assertStringStartsWith('se procedió con el análisis integral', $surtidaBridge);
        $this->assertStringNotContainsString('no asistió a la citación', $surtidaBridge);
        $this->assertStringContainsString('con el fin de escuchar sus descargos', $citadoBridge);
        $this->assertStringContainsString('usted no asistió a la citación', $citadoBridge);
        $this->assertStringContainsString('se procedió con el análisis integral', $citadoBridge);

        $this->assertSame('trabajador', $male->foGj46WorkerNoun());
        $this->assertSame('trabajadora', $female->foGj46WorkerNoun());
        $this->assertStringContainsString('calidad de trabajadora', $female->foGj46ExhortationParagraph1());
        $this->assertStringContainsString('calidad de trabajador,', $male->foGj46ExhortationParagraph1());
        $this->assertStringContainsString('le invitamos a revisar minuciosamente', $male->foGj46ExhortationParagraph2());
    }

    public function test_fo_gj_47_fixed_paragraphs(): void
    {
        $male = WorkerLegalPhrasing::fromGender(EmployeeGender::Masculino);
        $female = WorkerLegalPhrasing::fromGender(EmployeeGender::Femenino);

        $this->assertStringContainsString('para el trabajador la interrupción', $male->foGj47SuspensionEffectParagraph());
        $this->assertStringContainsString('para la trabajadora la interrupción', $female->foGj47SuspensionEffectParagraph());
        $this->assertStringContainsString('versión libre justifiquen su actuar', $male->foGj47FactsAnalysisParagraph());
        $this->assertStringContainsString('SJ SEGURIDAD LTDA', $male->foGj47PostArticlesClosingParagraph());
        $this->assertStringContainsString('dos (02) días hábiles', $male->foGj47AppealParagraph());
    }
}
