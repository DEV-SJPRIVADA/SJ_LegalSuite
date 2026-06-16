<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FoGj04DraftService
{
    public const MANIFESTATION_WANTS_TO_RESPOND = 'yes';

    public const MANIFESTATION_REFUSES_TO_RESPOND = 'no';

    /** @return array<string, string> */
    public static function manifestationOptions(): array
    {
        return [
            self::MANIFESTATION_WANTS_TO_RESPOND => 'SI QUIERO RESPONDER.',
            self::MANIFESTATION_REFUSES_TO_RESPOND => 'NO DESEA RESPONDER.',
        ];
    }

    public static function manifestationLabel(?string $value): string
    {
        return self::manifestationOptions()[$value ?? ''] ?? '';
    }

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer']);

        $existing = $case->fo_gj_04_payload ?? [];
        $hearingDate = $case->citation_confirmed_date;
        $tz = 'America/Bogota';

        $openingTime = '';
        $label = $case->resolvedDiligenceHearingTimeLabel();
        if ($label !== null) {
            try {
                $openingTime = Carbon::parse($label)->format('h:i A');
            } catch (\Throwable) {
                $openingTime = $label;
            }
        }

        return [
            'worker_manifestation' => (string) ($existing['worker_manifestation'] ?? ''),
            'closing_time' => (string) ($existing['closing_time'] ?? ''),
            'questions' => $this->questionsForForm($existing['questions'] ?? []),
            'opening_day' => $hearingDate ? (string) $hearingDate->day : '',
            'opening_month' => $hearingDate ? $this->spanishMonthName($hearingDate) : '',
            'opening_year' => $hearingDate ? (string) $hearingDate->year : '',
            'opening_time' => (string) ($existing['opening_time'] ?? $openingTime),
        ];
    }

    public function hasDraftCompleted(DisciplinaryCase $case): bool
    {
        return $case->fo_gj_04_draft_completed_at !== null
            && is_array($case->fo_gj_04_payload)
            && $case->fo_gj_04_payload !== [];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            $missing[] = 'El expediente debe estar en etapa de diligencia';
        }

        if ($case->citation_confirmed_date === null) {
            $missing[] = 'Fecha de diligencia confirmada';
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ATTENDED) {
            $missing[] = 'Registro de asistencia del trabajador';
        }

        if (! $this->hasDraftCompleted($case)) {
            $missing[] = 'Borrador FO-GJ-04 diligenciado';
        }

        foreach ($this->missingFo03CitationData($case) as $item) {
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
                'fo_gj_04' => 'Solo el abogado titular puede diligenciar el FO-GJ-04.',
            ]);
        }

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            throw ValidationException::withMessages([
                'fo_gj_04' => 'El acta FO-GJ-04 solo se diligencia en etapa de diligencia.',
            ]);
        }

        if ($case->fo_gj_04_generated_at !== null) {
            throw ValidationException::withMessages([
                'fo_gj_04' => 'El FO-GJ-04 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ATTENDED) {
            throw ValidationException::withMessages([
                'fo_gj_04' => 'El acta FO-GJ-04 solo aplica cuando el trabajador asistió.',
            ]);
        }

        $fo03Missing = $this->missingFo03CitationData($case);
        if ($fo03Missing !== []) {
            throw ValidationException::withMessages([
                'fo_gj_04' => 'Complete el FO-GJ-03 antes del acta. Falta: '.implode(', ', $fo03Missing).'.',
            ]);
        }

        $manifestation = (string) ($input['worker_manifestation'] ?? '');
        if (! array_key_exists($manifestation, self::manifestationOptions())) {
            throw ValidationException::withMessages([
                'foGj04WorkerManifestation' => 'Seleccione la manifestación del trabajador.',
            ]);
        }

        $closingTime = trim((string) ($input['closing_time'] ?? ''));
        if ($closingTime === '') {
            throw ValidationException::withMessages([
                'foGj04ClosingTime' => 'Indique la hora de finalización de la diligencia.',
            ]);
        }

        $openingTime = trim((string) ($input['opening_time'] ?? ''));
        if ($openingTime === '') {
            throw ValidationException::withMessages([
                'foGj04OpeningTime' => 'Indique la hora de inicio de la diligencia.',
            ]);
        }

        $rawQuestions = $input['questions'] ?? [];
        if (! is_array($rawQuestions)) {
            $rawQuestions = [];
        }

        $questions = $this->parseAndValidateQuestions($rawQuestions);

        if (! $actor->hasSignature()) {
            throw ValidationException::withMessages([
                'fo_gj_04' => 'Suba su firma digital en Mi perfil antes de guardar el FO-GJ-04.',
            ]);
        }

        $payload = [
            'worker_manifestation' => $manifestation,
            'closing_time' => $closingTime,
            'opening_time' => $openingTime,
            'questions' => $questions,
        ];

        $case->forceFill([
            'fo_gj_04_payload' => $payload,
            'fo_gj_04_draft_completed_at' => now(),
            'fo_gj_04_draft_completed_by' => $actor->id,
        ])->save();

        return $case->fresh();
    }

    public function saveWorkerSignature(DisciplinaryCase $case, User $actor, string $dataUri): DisciplinaryCase
    {
        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'foGj04WorkerSignature' => 'Solo el abogado titular puede registrar la firma del trabajador.',
            ]);
        }

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            throw ValidationException::withMessages([
                'foGj04WorkerSignature' => 'La firma del trabajador solo se registra en etapa de diligencia.',
            ]);
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ATTENDED) {
            throw ValidationException::withMessages([
                'foGj04WorkerSignature' => 'La firma del trabajador solo aplica cuando asistió a la diligencia.',
            ]);
        }

        if (! $this->hasDraftCompleted($case)) {
            throw ValidationException::withMessages([
                'foGj04WorkerSignature' => 'Complete el diligenciamiento del FO-GJ-04 antes de capturar la firma.',
            ]);
        }

        if ($case->fo_gj_04_generated_at !== null) {
            throw ValidationException::withMessages([
                'foGj04WorkerSignature' => 'El FO-GJ-04 ya fue generado.',
            ]);
        }

        $valid = app(CitationNotificationSigningService::class)->assertValidWorkerSignatureDataUri($dataUri);
        $payload = $case->fo_gj_04_payload ?? [];
        $payload['worker_signature_data_uri'] = $valid;

        $case->forceFill([
            'fo_gj_04_payload' => $payload,
        ])->save();

        return $case->fresh();
    }

    public function hasWorkerSignature(DisciplinaryCase $case): bool
    {
        $payload = $case->fo_gj_04_payload ?? [];

        return filled($payload['worker_signature_data_uri'] ?? null);
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        if (! $this->hasDraftCompleted($case)) {
            throw ValidationException::withMessages([
                'fo_gj_04' => 'Complete el diligenciamiento del FO-GJ-04 antes de generar o previsualizar el documento.',
            ]);
        }

        return $case->fo_gj_04_payload ?? [];
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{question: string, answer: string}>
     */
    public function questionsForForm(array $raw): array
    {
        $items = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $items[] = [
                'question' => (string) ($row['question'] ?? $row['text'] ?? ''),
                'answer' => (string) ($row['answer'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{question: string, answer: string}>
     */
    public function parseAndValidateQuestions(array $raw): array
    {
        $questions = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $question = self::formatQuestionMarks((string) ($row['question'] ?? $row['text'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            if ($question === '') {
                throw ValidationException::withMessages([
                    'foGj04Questions' => 'Cada ítem del cuestionario debe tener el texto de la pregunta.',
                ]);
            }

            if ($answer === '') {
                throw ValidationException::withMessages([
                    'foGj04Questions' => 'La respuesta del trabajador es obligatoria en cada pregunta.',
                ]);
            }

            $questions[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        if ($questions === []) {
            throw ValidationException::withMessages([
                'foGj04Questions' => 'Agregue al menos una pregunta al cuestionario.',
            ]);
        }

        return $questions;
    }

    public static function formatQuestionMarks(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\?+$/u', '', $text) ?? $text;
        $text = preg_replace('/^¿+/u', '', $text) ?? $text;
        $text = trim($text);

        return '¿'.$text.'?';
    }

    /**
     * Fecha del incumplimiento y formulación de cargos tomados del FO-GJ-03 diligenciado.
     *
     * @return array{breach_day: string, breach_month: string, breach_year: string, charges_description: string}
     */
    public function citationDataFromFo03(DisciplinaryCase $case): array
    {
        $fo03 = $case->fo_gj_03_payload ?? [];
        $breachDay = '';
        $breachMonth = '';
        $breachYear = '';

        $breachDate = $fo03['breach_date'] ?? null;
        if (filled($breachDate)) {
            try {
                $date = Carbon::parse($breachDate)->timezone('America/Bogota');
                $breachDay = (string) $date->day;
                $breachMonth = $this->spanishMonthName($date);
                $breachYear = (string) $date->year;
            } catch (\Throwable) {
                $breachDay = '';
                $breachMonth = '';
                $breachYear = '';
            }
        }

        return [
            'breach_day' => $breachDay,
            'breach_month' => $breachMonth,
            'breach_year' => $breachYear,
            'charges_description' => trim((string) ($fo03['charges_description'] ?? '')),
        ];
    }

    /** @return list<string> */
    public function missingFo03CitationData(DisciplinaryCase $case): array
    {
        $data = $this->citationDataFromFo03($case);
        $missing = [];

        if ($data['breach_day'] === '' || $data['breach_month'] === '' || $data['breach_year'] === '') {
            $missing[] = 'fecha del incumplimiento en FO-GJ-03';
        }

        if ($data['charges_description'] === '') {
            $missing[] = 'formulación de cargos en FO-GJ-03';
        }

        return $missing;
    }

    private function spanishMonthName(Carbon $date): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $months[(int) $date->month] ?? $date->format('F');
    }
}
