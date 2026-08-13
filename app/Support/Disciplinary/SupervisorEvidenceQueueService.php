<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cola de evidencias pendientes para supervisores de campo.
 */
final class SupervisorEvidenceQueueService
{
    public const QUEUE_CITATION = 'citation';

    public const QUEUE_DECISION = 'decision';

    public function __construct(
        private readonly FieldDisciplinaryScopeService $scope,
    ) {}

    /**
     * @return array{citation: int, decision: int, total: int}
     */
    public function counts(User $supervisor): array
    {
        $citation = $this->citationQuery($supervisor)->count();
        $decision = DecisionWorkflowSchema::isReady()
            ? $this->decisionQuery($supervisor)->count()
            : 0;

        return [
            'citation' => $citation,
            'decision' => $decision,
            'total' => $citation + $decision,
        ];
    }

    /**
     * @return Collection<int, array{
     *     queue_type: string,
     *     case: DisciplinaryCase,
     *     document_label: string,
     *     status_label: string,
     *     notification_summary: string,
     *     generated_at: ?Carbon,
     * }>
     */
    public function tasks(
        User $supervisor,
        string $activeQueue = '',
        string $search = '',
    ): Collection {
        $rows = collect();

        if ($activeQueue === '' || $activeQueue === self::QUEUE_CITATION) {
            $rows = $rows->merge(
                $this->citationQuery($supervisor)
                    ->when($search !== '', fn (Builder $q) => $this->applySearch($q, $search))
                    ->get()
                    ->map(fn (DisciplinaryCase $case) => $this->mapCitationRow($case))
            );
        }

        if (DecisionWorkflowSchema::isReady()
            && ($activeQueue === '' || $activeQueue === self::QUEUE_DECISION)) {
            $rows = $rows->merge(
                $this->decisionQuery($supervisor)
                    ->when($search !== '', fn (Builder $q) => $this->applySearch($q, $search))
                    ->get()
                    ->map(fn (DisciplinaryCase $case) => $this->mapDecisionRow($case))
            );
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['generated_at']?->timestamp ?? 0)
            ->values();
    }

    /** @return Builder<DisciplinaryCase> */
    public function citationQuery(User $supervisor): Builder
    {
        return $this->applyFieldEmployeeScope(
            DisciplinaryCase::query()
                ->whereHas('notificationSupervisionZone.users', fn (Builder $users) => $users
                    ->whereKey($supervisor->id))
                ->whereNotNull('fo_gj_03_generated_at')
                ->whereNull('citation_evidence_uploaded_at')
                ->whereHas('documents', fn ($documents) => $documents
                    ->where('document_type', DocumentType::CITACION)
                    ->where('notes', 'like', '%'.DisciplinaryCase::NOTE_FO_GJ_03_GENERATED.'%'))
                ->with([
                    'employee:id,first_name,last_name,document_number,municipality_code,employee_job_position_id',
                    'employee.employeeJobPosition:id,is_guarda',
                    'notificationSupervisionZone:id,name',
                ]),
            $supervisor,
        );
    }

    /** @return Builder<DisciplinaryCase> */
    public function decisionQuery(User $supervisor): Builder
    {
        return $this->applyFieldEmployeeScope(
            DisciplinaryCase::query()
                ->whereHas('decisionNotificationSupervisionZone.users', fn (Builder $users) => $users
                    ->whereKey($supervisor->id))
                ->whereNotNull('decision_comunicado_generated_at')
                ->whereNull('decision_evidence_uploaded_at')
                ->with([
                    'employee:id,first_name,last_name,document_number,municipality_code,employee_job_position_id',
                    'employee.employeeJobPosition:id,is_guarda',
                    'decisionNotificationSupervisionZone:id,name',
                ]),
            $supervisor,
        );
    }

    /** @param Builder<DisciplinaryCase> $query */
    private function applyFieldEmployeeScope(Builder $query, User $supervisor): Builder
    {
        if (! $this->scope->requiresTerritorialScope($supervisor)) {
            return $query;
        }

        if (! $this->scope->hasConfiguredScope($supervisor)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'employee',
            fn (Builder $employee) => $this->scope->applyEmployeeScope($employee, $supervisor),
        );
    }

    /** @return array{queue_type: string, case: DisciplinaryCase, document_label: string, status_label: string, notification_summary: string, generated_at: ?Carbon} */
    private function mapCitationRow(DisciplinaryCase $case): array
    {
        return [
            'queue_type' => self::QUEUE_CITATION,
            'case' => $case,
            'document_label' => 'FO-GJ-03',
            'status_label' => 'Citación pendiente',
            'notification_summary' => $this->formatNotificationSlot(
                $case->notification_date,
                $case->notification_shift,
                $case->notification_zone,
                $case->notification_supervision_zone_name,
            ),
            'generated_at' => $case->fo_gj_03_generated_at,
        ];
    }

    /** @return array{queue_type: string, case: DisciplinaryCase, document_label: string, status_label: string, notification_summary: string, generated_at: ?Carbon} */
    private function mapDecisionRow(DisciplinaryCase $case): array
    {
        return [
            'queue_type' => self::QUEUE_DECISION,
            'case' => $case,
            'document_label' => 'Comunicado',
            'status_label' => 'Decisión pendiente',
            'notification_summary' => $this->formatNotificationSlot(
                $case->decision_notification_date,
                $case->decision_notification_shift,
                $case->decision_notification_zone,
                $case->decision_notification_supervision_zone_name,
            ),
            'generated_at' => $case->decision_comunicado_generated_at,
        ];
    }

    private function formatNotificationSlot(
        mixed $date,
        ?string $shift,
        ?string $place,
        ?string $supervisionZoneName = null,
    ): string {
        $parts = [];

        if ($date instanceof Carbon) {
            $parts[] = $date->timezone('America/Bogota')->format('d/m/Y');
        } elseif (is_string($date) && $date !== '') {
            try {
                $parts[] = Carbon::parse($date)->timezone('America/Bogota')->format('d/m/Y');
            } catch (\Throwable) {
                $parts[] = $date;
            }
        }

        if (filled($shift)) {
            $parts[] = $shift;
        }

        if (filled($place)) {
            $parts[] = $place;
        }

        if (filled($supervisionZoneName)) {
            $parts[] = 'Zona de supervisión: '.$supervisionZoneName;
        }

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    /** @param Builder<DisciplinaryCase> $query */
    private function applySearch(Builder $query, string $search): void
    {
        $term = '%'.trim($search).'%';

        $query->where(function (Builder $inner) use ($term): void {
            $inner->where('case_number', 'like', $term)
                ->orWhereHas('employee', function (Builder $employee) use ($term): void {
                    $employee->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('document_number', 'like', $term);
                });
        });
    }
}
