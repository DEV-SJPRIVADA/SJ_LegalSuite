<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\SpanishDateParts;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FoGj44DraftService
{
    public function __construct(
        private readonly FoGj04DraftService $foGj04Drafts,
    ) {}

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->fo_gj_44_payload ?? [];
        $now = now()->timezone('America/Bogota');
        $signParts = SpanishDateParts::fromDate($now);

        return [
            'sign_time' => (string) ($existing['sign_time'] ?? $now->format('h:i A')),
            'witness1_name' => (string) ($existing['witness1_name'] ?? ''),
            'witness1_cargo' => (string) ($existing['witness1_cargo'] ?? ''),
            'witness1_date' => (string) ($existing['witness1_date'] ?? $now->format('d/m/Y')),
            'witness2_name' => (string) ($existing['witness2_name'] ?? ''),
            'witness2_cargo' => (string) ($existing['witness2_cargo'] ?? ''),
            'witness2_date' => (string) ($existing['witness2_date'] ?? $now->format('d/m/Y')),
            'sign_day' => (string) ($existing['sign_day'] ?? $signParts['day']),
            'sign_month' => (string) ($existing['sign_month'] ?? $signParts['month']),
            'sign_year_suffix' => (string) ($existing['sign_year_suffix'] ?? $signParts['year_suffix']),
        ];
    }

    public function hasDraftCompleted(DisciplinaryCase $case): bool
    {
        return $case->fo_gj_44_draft_completed_at !== null
            && is_array($case->fo_gj_44_payload)
            && $case->fo_gj_44_payload !== [];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            $missing[] = 'El expediente debe estar en etapa de diligencia';
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ABSENT) {
            $missing[] = 'Registro de inasistencia del trabajador';
        }

        if ($case->citation_confirmed_date === null) {
            $missing[] = 'Fecha de diligencia confirmada';
        }

        if (! $this->hasDraftCompleted($case)) {
            $missing[] = 'Borrador FO-GJ-44 diligenciado';
        }

        foreach ($this->foGj04Drafts->missingFo03CitationData($case) as $item) {
            $missing[] = $item;
        }

        return $missing;
    }

    public function isReadyForPdf(DisciplinaryCase $case): bool
    {
        return $this->missingDraftRequirements($case) === [];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveDraft(DisciplinaryCase $case, User $actor, array $input): DisciplinaryCase
    {
        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'Solo el abogado titular puede diligenciar el FO-GJ-44.',
            ]);
        }

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'El FO-GJ-44 solo se diligencia en etapa de diligencia.',
            ]);
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ABSENT) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'El FO-GJ-44 solo aplica cuando el trabajador no asistió.',
            ]);
        }

        if ($case->fo_gj_44_generated_at !== null) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'El FO-GJ-44 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        $fo03Missing = $this->foGj04Drafts->missingFo03CitationData($case);
        if ($fo03Missing !== []) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'Complete el FO-GJ-03 antes de la constancia. Falta: '.implode(', ', $fo03Missing).'.',
            ]);
        }

        $signTime = trim((string) ($input['sign_time'] ?? ''));
        if ($signTime === '') {
            throw ValidationException::withMessages([
                'foGj44SignTime' => 'Indique la hora de firma del documento.',
            ]);
        }

        $witnesses = [];
        foreach ([1, 2] as $index) {
            $name = trim((string) ($input["witness{$index}_name"] ?? ''));
            $cargo = trim((string) ($input["witness{$index}_cargo"] ?? ''));
            $date = trim((string) ($input["witness{$index}_date"] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    "foGj44Witness{$index}Name" => "Indique el nombre del testigo {$index}.",
                ]);
            }

            if ($cargo === '') {
                throw ValidationException::withMessages([
                    "foGj44Witness{$index}Cargo" => "Indique el cargo del testigo {$index}.",
                ]);
            }

            if ($date === '') {
                throw ValidationException::withMessages([
                    "foGj44Witness{$index}Date" => "Indique la fecha del testigo {$index}.",
                ]);
            }

            $witnesses[$index] = compact('name', 'cargo', 'date');
        }

        $signDay = trim((string) ($input['sign_day'] ?? ''));
        $signMonth = trim((string) ($input['sign_month'] ?? ''));
        $signYearSuffix = trim((string) ($input['sign_year_suffix'] ?? ''));

        if ($signDay === '' || $signMonth === '' || $signYearSuffix === '') {
            throw ValidationException::withMessages([
                'foGj44SignDate' => 'Indique el día, mes y año de firma del documento.',
            ]);
        }

        if (! $actor->hasSignature()) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'Suba su firma digital en Mi perfil antes de guardar el FO-GJ-44.',
            ]);
        }

        $payload = [
            'sign_time' => $signTime,
            'sign_day' => $signDay,
            'sign_month' => $signMonth,
            'sign_year_suffix' => $signYearSuffix,
            'witness1_name' => $witnesses[1]['name'],
            'witness1_cargo' => $witnesses[1]['cargo'],
            'witness1_date' => $witnesses[1]['date'],
            'witness2_name' => $witnesses[2]['name'],
            'witness2_cargo' => $witnesses[2]['cargo'],
            'witness2_date' => $witnesses[2]['date'],
        ];

        $case->forceFill([
            'fo_gj_44_payload' => $payload,
            'fo_gj_44_draft_completed_at' => now(),
            'fo_gj_44_draft_completed_by' => $actor->id,
        ])->save();

        return $case->fresh();
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        if (! $this->hasDraftCompleted($case)) {
            throw ValidationException::withMessages([
                'fo_gj_44' => 'Complete el diligenciamiento del FO-GJ-44 antes de generar o previsualizar el documento.',
            ]);
        }

        return $case->fo_gj_44_payload ?? [];
    }

    /** @return array{day: string, month: string, year_suffix: string} */
    public function citationSinceParts(DisciplinaryCase $case): array
    {
        $since = $case->fo_gj_03_generated_at
            ?? $case->notification_date
            ?? $case->citation_evidence_uploaded_at;

        if ($since instanceof Carbon) {
            $parts = SpanishDateParts::fromDate($since->timezone('America/Bogota'));

            return [
                'day' => $parts['day'],
                'month' => $parts['month'],
                'year_suffix' => $parts['year_suffix'],
            ];
        }

        if (is_string($since) && filled($since)) {
            try {
                $parts = SpanishDateParts::fromDate(Carbon::parse($since)->timezone('America/Bogota'));

                return [
                    'day' => $parts['day'],
                    'month' => $parts['month'],
                    'year_suffix' => $parts['year_suffix'],
                ];
            } catch (\Throwable) {
                // fall through
            }
        }

        return ['day' => '', 'month' => '', 'year_suffix' => ''];
    }
}
