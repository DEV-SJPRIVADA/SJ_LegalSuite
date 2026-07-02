<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\CitationEvidenceType;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Caso disciplinario. Es la raíz del agregado.
 *
 * IMPORTANTE: nunca modificar `current_status` directamente desde código de aplicación.
 * Toda transición debe pasar por DisciplinaryWorkflowService::transition() para
 * garantizar:
 *   - Validación de transición permitida
 *   - Generación de DisciplinaryAction (audit log)
 *   - Aplicación de side-effects (creación de stages, deadlines, etc.)
 */
class DisciplinaryCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_number',
        'employee_id',
        'reporter_id',
        'assigned_lawyer_id',
        'assigned_operator_id',
        'assigned_planner_id',
        'city',
        'municipality_code',
        'sede',
        'current_status',
        'current_stage_type',
        'decision',
        'decision_notes',
        'decided_at',
        'opened_at',
        'closed_at',
        'summary',
        'metadata',
        'coordination_started_at',
        'citation_confirmed_date',
        'citation_confirmed_time',
        'citation_confirmed_by',
        'citation_selected_message_id',
        'fo_gj_03_generated_at',
        'fo_gj_03_generated_by',
        'fo_gj_03_payload',
        'fo_gj_03_draft_completed_at',
        'fo_gj_03_draft_completed_by',
        'fo_gj_04_payload',
        'fo_gj_04_draft_completed_at',
        'fo_gj_04_draft_completed_by',
        'fo_gj_04_generated_at',
        'fo_gj_04_generated_by',
        'diligence_attendance',
        'diligence_attendance_registered_at',
        'diligence_attendance_registered_by',
        'fo_gj_44_payload',
        'fo_gj_44_draft_completed_at',
        'fo_gj_44_draft_completed_by',
        'fo_gj_44_generated_at',
        'fo_gj_44_generated_by',
        'fo_gj_54_payload',
        'fo_gj_54_draft_completed_at',
        'fo_gj_54_draft_completed_by',
        'fo_gj_54_generated_at',
        'fo_gj_54_generated_by',
        'diligence_justification_received_at',
        'diligence_justification_received_by',
        'diligence_justification_notes',
        'comite_payload',
        'comite_draft_completed_at',
        'comite_draft_completed_by',
        'comite_generated_at',
        'comite_generated_by',
        'decision_coordination_started_at',
        'decision_coordination_started_by',
        'decision_payload',
        'decision_draft_completed_at',
        'decision_draft_completed_by',
        'decision_comunicado_generated_at',
        'decision_comunicado_generated_by',
        'decision_notification_completed_at',
        'decision_notification_message_id',
        'decision_notification_date',
        'decision_notification_shift',
        'decision_notification_zone',
        'decision_notification_supervisor_user_id',
        'decision_notification_supervisor_name',
        'decision_notification_notes',
        'decision_notification_supervisor_assigned_at',
        'decision_notification_supervisor_assigned_by',
        'decision_evidence_type',
        'decision_evidence_uploaded_at',
        'decision_hr_review_completed_at',
        'decision_hr_review_completed_by',
        'citation_evidence_type',
        'citation_evidence_uploaded_at',
        'notification_requested_at',
        'notification_requested_by',
        'notification_information_completed_at',
        'notification_information_message_id',
        'notification_date',
        'notification_shift',
        'notification_zone',
        'notification_supervisor_user_id',
        'notification_supervisor_name',
        'notification_notes',
        'notification_supervisor_assigned_at',
        'notification_supervisor_assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'current_status' => CaseStatus::class,
            'current_stage_type' => StageType::class,
            'decision' => Decision::class,
            'opened_at' => 'date',
            'closed_at' => 'date',
            'decided_at' => 'date',
            'metadata' => 'array',
            'coordination_started_at' => 'datetime',
            'citation_confirmed_date' => 'date',
            'citation_evidence_type' => CitationEvidenceType::class,
            'fo_gj_03_payload' => 'array',
            'fo_gj_03_draft_completed_at' => 'datetime',
            'fo_gj_03_generated_at' => 'datetime',
            'fo_gj_04_payload' => 'array',
            'fo_gj_04_draft_completed_at' => 'datetime',
            'fo_gj_04_generated_at' => 'datetime',
            'diligence_attendance' => DiligenceAttendance::class,
            'diligence_attendance_registered_at' => 'datetime',
            'fo_gj_44_payload' => 'array',
            'fo_gj_44_draft_completed_at' => 'datetime',
            'fo_gj_44_generated_at' => 'datetime',
            'fo_gj_54_payload' => 'array',
            'fo_gj_54_draft_completed_at' => 'datetime',
            'fo_gj_54_generated_at' => 'datetime',
            'diligence_justification_received_at' => 'datetime',
            'comite_payload' => 'array',
            'comite_draft_completed_at' => 'datetime',
            'comite_generated_at' => 'datetime',
            'decision_coordination_started_at' => 'datetime',
            'decision_payload' => 'array',
            'decision_draft_completed_at' => 'datetime',
            'decision_comunicado_generated_at' => 'datetime',
            'decision_notification_completed_at' => 'datetime',
            'decision_notification_date' => 'date',
            'decision_notification_supervisor_assigned_at' => 'datetime',
            'decision_evidence_uploaded_at' => 'datetime',
            'decision_hr_review_completed_at' => 'datetime',
            'citation_evidence_uploaded_at' => 'datetime',
            'notification_requested_at' => 'datetime',
            'notification_information_completed_at' => 'datetime',
            'notification_date' => 'date',
            'notification_supervisor_assigned_at' => 'datetime',
        ];
    }

    // ---------- Relaciones ----------

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
    }

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function assignedPlanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_planner_id');
    }

    public function notificationSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notification_supervisor_user_id');
    }

    public function decisionNotificationSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_notification_supervisor_user_id');
    }

    public function notificationRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notification_requested_by');
    }

    public function faults(): BelongsToMany
    {
        return $this->belongsToMany(Fault::class, 'disciplinary_case_fault')
            ->withPivot('extra_info')
            ->withTimestamps();
    }

    public function stages(): HasMany
    {
        return $this->hasMany(DisciplinaryStage::class)->orderBy('sequence');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class)->orderByDesc('performed_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DisciplinaryDocument::class)->latest();
    }

    public function agendaThread(): HasOne
    {
        return $this->hasOne(DisciplinaryAgendaThread::class, 'disciplinary_case_id');
    }

    /**
     * Envío FO-GJ-51 que originó este expediente (tras autorización queda vinculado por disciplinary_case_id).
     */
    public function informeSubmission(): HasOne
    {
        return $this->hasOne(InformeSubmission::class, 'disciplinary_case_id');
    }

    /**
     * Estados en los que opera el hilo de coordinación citación FO-GJ-03 (abogado ↔ planeación).
     */
    public static function statusesAllowingAgendaCoordination(): array
    {
        return [
            CaseStatus::CITACION_PROGRAMADA,
            CaseStatus::REPROGRAMADO,
            CaseStatus::DECISION,
        ];
    }

    /**
     * Hilo chat con planeación: citación, reprogramación o decisión (con coordinación iniciada).
     */
    public function allowsAgendaThread(): bool
    {
        if ($this->assigned_lawyer_id === null) {
            return false;
        }

        if ($this->current_status === CaseStatus::DECISION) {
            return $this->decision_coordination_started_at !== null;
        }

        if ($this->coordination_started_at === null) {
            return false;
        }

        return in_array($this->current_status, self::statusesAllowingAgendaCoordination(), true);
    }

    public function hasCoordinationStarted(): bool
    {
        return $this->coordination_started_at !== null;
    }

    /** Etapa C activa: diligencia disciplinaria y acta FO-GJ-04. */
    public function isDiligenciaStageActive(): bool
    {
        return $this->current_status === CaseStatus::DILIGENCIA;
    }

    /**
     * Panel Etapa C visible: diligencia en curso o seguimiento por inasistencia (justificación).
     */
    public function showsComiteStagePanel(): bool
    {
        return $this->current_status === CaseStatus::COMITE_DISCIPLINARIO;
    }

    /** Etapa D activa: comunicado de decisión / cierre. */
    public function showsDecisionStagePanel(): bool
    {
        return $this->current_status === CaseStatus::DECISION;
    }

    /**
     * Etapa C visible en solo lectura tras avanzar a decisión.
     */
    public function showsDiligenceStageReadOnly(): bool
    {
        if ($this->showsDiligenceStagePanel()) {
            return false;
        }

        if ($this->current_status !== CaseStatus::DECISION) {
            return false;
        }

        return $this->fo_gj_04_generated_at !== null
            || $this->comite_generated_at !== null
            || $this->latestActaDiligenciaDocument() !== null
            || $this->latestComiteActaDocument() !== null;
    }

    public function showsDiligenceStagePanel(): bool
    {
        if ($this->showsComiteStagePanel()) {
            return true;
        }

        if ($this->current_status === CaseStatus::DILIGENCIA) {
            return true;
        }

        return $this->current_status === CaseStatus::JUSTIFICACION_PENDIENTE
            && $this->diligence_attendance === DiligenceAttendance::ABSENT;
    }

    /**
     * Etapa B visible en solo lectura (coordinación cerrada al avanzar a diligencia).
     */
    public function showsCitationStageReadOnly(): bool
    {
        if ($this->showsComiteStagePanel()) {
            return false;
        }

        if (! $this->showsDiligenceStagePanel()) {
            return false;
        }

        return $this->hasCoordinationStarted()
            || $this->fo_gj_03_generated_at !== null
            || $this->citation_confirmed_date !== null;
    }

    /**
     * Hora de diligencia para UI: prioriza FO-GJ-03 diligenciado, luego slot confirmado.
     */
    public function resolvedDiligenceHearingTimeLabel(): ?string
    {
        $raw = $this->resolveDiligenceHearingTimeRaw();

        return $raw !== null ? self::formatHearingTimeLabel($raw) : null;
    }

    public static function formatHearingTimeLabel(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '—';
        }

        foreach (['H:i:s', 'H:i', 'h:i A', 'g:i A'] as $pattern) {
            try {
                return Carbon::createFromFormat($pattern, $raw)->format('h:i A');
            } catch (\Throwable) {
                // siguiente patrón
            }
        }

        try {
            return Carbon::parse($raw)->format('h:i A');
        } catch (\Throwable) {
            try {
                return Carbon::parse('today '.$raw)->format('h:i A');
            } catch (\Throwable) {
                return $raw;
            }
        }
    }

    private function resolveDiligenceHearingTimeRaw(): ?string
    {
        $payload = $this->fo_gj_03_payload ?? [];
        $fromPayload = trim((string) ($payload['hearing_time'] ?? ''));
        if ($fromPayload !== '') {
            return $fromPayload;
        }

        if (filled($this->citation_confirmed_time)) {
            return (string) $this->citation_confirmed_time;
        }

        if ($this->citation_confirmed_date !== null && $this->citation_selected_message_id !== null) {
            $this->loadMissing('agendaThread.messages');
            $confirmedDate = $this->citation_confirmed_date->format('Y-m-d');

            foreach ($this->agendaThread?->messages ?? [] as $message) {
                if ((int) $message->id !== (int) $this->citation_selected_message_id) {
                    continue;
                }

                foreach ($message->normalizedProposedSlots() as $slot) {
                    if (($slot['date'] ?? '') === $confirmedDate && filled($slot['time'] ?? null)) {
                        return (string) $slot['time'];
                    }
                }

                break;
            }
        }

        return null;
    }

    public function canStartCoordination(): bool
    {
        return $this->current_status === CaseStatus::CITACION_PROGRAMADA
            && $this->assigned_lawyer_id !== null
            && ! $this->hasCoordinationStarted();
    }

    /** Indica si planeación ya intervino en el hilo (métricas / UX). */
    public function hasAgendaPlanningReply(): bool
    {
        return $this->agendaThread?->hasPlanningReply() ?? false;
    }

    /** Planeación publicó al menos un mensaje con fechas de diligencia estructuradas. */
    public function hasPlanningProposedSlots(): bool
    {
        $this->loadMissing('agendaThread.messages');

        foreach ($this->agendaThread?->messages ?? [] as $message) {
            if ($message->message_kind !== AgendaMessageKind::PLANNING_RESPONSE) {
                continue;
            }
            if ($message->normalizedProposedSlots() !== []) {
                return true;
            }
        }

        return false;
    }

    public function awaitingPlanningDiligenceSlots(): bool
    {
        return $this->hasCoordinationStarted()
            && $this->citation_confirmed_date === null
            && ! $this->hasPlanningProposedSlots();
    }

    /** Planeación publicó programación de decisión en el hilo. */
    public function hasDecisionPlanningReply(): bool
    {
        $this->loadMissing('agendaThread.messages');

        foreach ($this->agendaThread?->messages ?? [] as $message) {
            if ($message->message_kind === AgendaMessageKind::DECISION_PLANNING_RESPONSE) {
                return true;
            }
        }

        return false;
    }

    public function awaitingDecisionPlanningSlots(): bool
    {
        return $this->current_status === CaseStatus::DECISION
            && $this->decision_coordination_started_at !== null
            && ! $this->hasDecisionPlanningReply();
    }

    public function currentStage(): HasMany
    {
        return $this->hasMany(DisciplinaryStage::class)
            ->whereIn('status', ['pendiente', 'en_curso'])
            ->orderByDesc('sequence');
    }

    // ---------- Scopes (filtros del listado y dashboard) ----------

    public function scopeWithStatus(Builder $query, CaseStatus|string $status): Builder
    {
        return $query->where('current_status', $status instanceof CaseStatus ? $status->value : $status);
    }

    public function scopeBucket(Builder $query, CaseBucket $bucket): Builder
    {
        $statuses = collect(CaseStatus::cases())
            ->filter(fn (CaseStatus $s) => $s->bucket() === $bucket)
            ->map(fn (CaseStatus $s) => $s->value)
            ->all();

        return $query->whereIn('current_status', $statuses);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_lawyer_id', $userId);
    }

    /** Etapa informe sin abogado titular (bandeja compartida de abogados). */
    public function scopeInInformePool(Builder $query): Builder
    {
        return $query
            ->where('current_status', CaseStatus::INFORME->value)
            ->whereNull('assigned_lawyer_id');
    }

    public function isInInformePool(): bool
    {
        return $this->current_status === CaseStatus::INFORME
            && $this->assigned_lawyer_id === null;
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(ColombianMunicipality::class, 'municipality_code', 'municipality_code');
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        if (preg_match('/^\d{5}$/', $city) === 1) {
            return $query->where('municipality_code', $city);
        }

        return $query->where('city', $city);
    }

    public function scopeOpenedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('opened_at', [$from, $to]);
    }

    public function scopeWithFault(Builder $query, int $faultId): Builder
    {
        return $query->whereHas('faults', fn (Builder $q) => $q->where('faults.id', $faultId));
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(' ', '%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('case_number', 'like', $like)
                ->orWhere('summary', 'like', $like)
                ->orWhereHas('employee', fn (Builder $p) => $p->search(trim((string) $like, '%')));
        });
    }

    /**
     * Planeación (bandeja tipo informes/agendas): citación o reprogramación con mensaje inicial del abogado en el hilo.
     */
    public function scopeForPlaneacionInbox(Builder $query): Builder
    {
        $statuses = array_map(static fn (CaseStatus $s) => $s->value, self::statusesAllowingAgendaCoordination());

        return $query
            ->whereIn('disciplinary_cases.current_status', $statuses)
            ->whereNotNull('disciplinary_cases.assigned_lawyer_id')
            ->whereNotNull('disciplinary_cases.coordination_started_at');
    }

    public function isVisibleToPlaneacionUser(): bool
    {
        return false;
    }

    /**
     * Alcance de casos visibles según rol: el perfil `abogado` (sin `admin`)
     * sólo ve procesos donde es el abogado asignado. Supervisores y operadores ven
     * el pool (expedientes ya formalizados, distintos de borrador), sin titular fijo.
     */
    public function scopeForDisciplinaryActor(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('abogado')) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('assigned_lawyer_id', $user->id)
                    ->orWhere(fn (Builder $pool) => $pool->inInformePool());
            });
        }

        if ($user->hasRole('operador')) {
            return $query->where('current_status', '!=', CaseStatus::BORRADOR->value);
        }

        if ($user->hasRole('supervisor')) {
            return $query->whereRaw('1=0');
        }

        if ($user->hasRole('programador')) {
            return $query->where('current_status', '!=', CaseStatus::BORRADOR->value);
        }

        if ($user->hasRole('planeacion')) {
            return $query->whereRaw('1=0');
        }

        if ($user->hasRole('operaciones')) {
            return $query->visibleToOperacionesReviewer($user);
        }

        return $query;
    }

    /**
     * Operaciones (GAP A2): solo expedientes con revisor asignado en FO-GJ-51 (`assigned_reviewer_id`),
     * los que reportó o todos si tiene dirección (`review-inform-all`). La columna Abogado no aplica aquí.
     */
    public function scopeVisibleToOperacionesReviewer(Builder $query, User $user): Builder
    {
        if (self::userCanReviewAllInformes($user)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('reporter_id', $user->id)
                ->orWhereHas('informeSubmission', fn (Builder $sub) => $sub->where('assigned_reviewer_id', $user->id));
        });
    }

    public function isVisibleToOperacionesReviewer(User $user): bool
    {
        if (self::userCanReviewAllInformes($user)) {
            return true;
        }

        if ((int) $this->reporter_id === (int) $user->id) {
            return true;
        }

        $this->loadMissing('informeSubmission');

        return $this->informeSubmission !== null
            && (int) $this->informeSubmission->assigned_reviewer_id === (int) $user->id;
    }

    public static function userCanReviewAllInformes(User $user): bool
    {
        try {
            return $user->hasPermissionTo('disciplinary.review-inform-all');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    // ---------- Helpers de dominio ----------

    public function bucket(): CaseBucket
    {
        return $this->current_status->bucket();
    }

    /**
     * Pool de campo operativo: no hay titular fijo; se excluye borrador interno.
     */
    public function isVisibleToDisciplinaryFieldPool(): bool
    {
        return $this->current_status !== CaseStatus::BORRADOR;
    }

    public function isFinalized(): bool
    {
        return $this->current_status->isTerminal();
    }

    /**
     * PDF del informe FO-GJ-51 incorporado al expediente (p. ej. copia autorizada en revisión).
     */
    public function primaryFo51InformeDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents->sortBy('id')->values()
            : $this->documents()->orderBy('id')->get();

        $informes = $docs->filter(fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::INFORME
            && $d->form_code === 'FO-GJ-51');

        $match = $informes->first(fn (DisciplinaryDocument $d) => str_contains((string) ($d->notes ?? ''), 'autorizado por dirección'));

        return $match instanceof DisciplinaryDocument ? $match : $informes->first();
    }

    /**
     * Evidencias del FO-GJ-51 conservadas al autorizar (imágenes copiadas al expediente).
     *
     * @return Collection<int, DisciplinaryDocument>
     */
    public function fo51AuthorizedEvidenceDocuments(): Collection
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderBy('id')->get();

        return $docs
            ->filter(fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::EVIDENCIA
                && ($d->notes ?? '') === DisciplinaryDocument::NOTE_FO51_AUTHORIZED_EVIDENCE)
            ->sortBy('id')
            ->values();
    }

    /**
     * Evidencias a mostrar junto al PDF del informe: copias FO-51 al autorizar y, en su defecto,
     * cualquier evidencia cargada en la misma etapa que el PDF del informe (etapa Informe).
     *
     * @return Collection<int, DisciplinaryDocument>
     */
    public function fo51InformePreviewEvidenceDocuments(?DisciplinaryDocument $primaryInforme = null): Collection
    {
        $informe = $primaryInforme ?? $this->primaryFo51InformeDocument();
        if (! $informe) {
            return collect();
        }

        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderBy('id')->get();

        $byNote = $docs->filter(
            fn (DisciplinaryDocument $d): bool => $d->document_type === DocumentType::EVIDENCIA
                && ($d->notes ?? '') === DisciplinaryDocument::NOTE_FO51_AUTHORIZED_EVIDENCE
        );

        $byStage = collect();
        if ($informe->disciplinary_stage_id !== null) {
            $stageId = (int) $informe->disciplinary_stage_id;
            $byStage = $docs->filter(
                fn (DisciplinaryDocument $d): bool => $d->document_type === DocumentType::EVIDENCIA
                    && $d->disciplinary_stage_id !== null
                    && (int) $d->disciplinary_stage_id === $stageId
            );
        }

        return $byNote->concat($byStage)->unique('id')->sortBy('id')->values();
    }

    public const NOTE_FO_GJ_03_GENERATED = 'FO-GJ-03 generado desde expediente';

    public const NOTE_FO_GJ_04_GENERATED = 'FO-GJ-04 generado desde expediente';

    public const NOTE_FO_GJ_44_GENERATED = 'FO-GJ-44 generado desde expediente';

    public const NOTE_FO_GJ_54_GENERATED = 'FO-GJ-54 generado desde expediente';

    public const NOTE_COMITE_ACTA_GENERATED = 'Acta de comité disciplinario generada desde expediente';

    public const NOTE_CITATION_EVIDENCE_PREFIX = 'Evidencia notificación citación';

    public const NOTE_DECISION_COMUNICADO_GENERATED = 'Comunicado de decisión generado desde expediente';

    public const NOTE_DECISION_EVIDENCE_PREFIX = 'Evidencia notificación decisión';

    public const NOTE_DECISION_HR_ANEXO_PREFIX = 'Anexo laboral gestión humana';

    /**
     * PDF de citación FO-GJ-03 generado desde el expediente (no evidencia de notificación).
     */
    public function primaryFoGj03CitationDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderBy('id')->get();

        $match = $docs->first(
            fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::CITACION
                && str_contains((string) ($d->notes ?? ''), self::NOTE_FO_GJ_03_GENERATED)
        );

        return $match instanceof DisciplinaryDocument ? $match : null;
    }

    /** Acta de diligencia disciplinaria (FO-GJ-04) más reciente en el expediente. */
    public function latestActaDiligenciaDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        $match = $docs->first(
            fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::ACTA_DILIGENCIA
        );

        return $match instanceof DisciplinaryDocument ? $match : null;
    }

    /** Constancia de inasistencia (FO-GJ-44) más reciente en el expediente. */
    public function latestConstanciaInasistenciaDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        $match = $docs->first(
            fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::CONSTANCIA_INASISTENCIA
        );

        return $match instanceof DisciplinaryDocument ? $match : null;
    }

    /** Acta de comité disciplinario más reciente en el expediente. */
    public function latestComiteActaDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        $match = $docs->first(
            fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::ACTA_COMITE
        );

        return $match instanceof DisciplinaryDocument ? $match : null;
    }

    /** Comunicado de decisión más reciente en el expediente. */
    public function latestDecisionComunicadoDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        $match = $docs->first(
            fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::DECISION
                && str_contains((string) ($d->notes ?? ''), self::NOTE_DECISION_COMUNICADO_GENERATED)
        );

        return $match instanceof DisciplinaryDocument ? $match : null;
    }

    public function canReceiveDecisionEvidence(): bool
    {
        return $this->decision_comunicado_generated_at !== null
            || $this->latestDecisionComunicadoDocument() !== null;
    }

    public function hasDecisionHrAnnex(): bool
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        return $docs->contains(
            fn (DisciplinaryDocument $d) => str_contains((string) ($d->notes ?? ''), self::NOTE_DECISION_HR_ANEXO_PREFIX)
        );
    }

    /** @return \Illuminate\Support\Collection<int, DisciplinaryDocument> */
    public function decisionHrAnnexDocuments(): \Illuminate\Support\Collection
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        return $docs->filter(
            fn (DisciplinaryDocument $d) => str_contains((string) ($d->notes ?? ''), self::NOTE_DECISION_HR_ANEXO_PREFIX)
        )->values();
    }

    /**
     * Usuarios autorizados para cargar evidencia de notificación de decisión (Etapa D).
     */
    public function canUserUploadDecisionEvidence(User $user): bool
    {
        if (! $this->canReceiveDecisionEvidence()) {
            return false;
        }

        if ($this->decision_evidence_uploaded_at !== null) {
            return false;
        }

        if ($user->hasRole('planeacion')) {
            return false;
        }

        if ((int) $this->assigned_lawyer_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole('admin') || $user->hasPermissionTo('disciplinary.assign')) {
            return true;
        }

        $this->loadMissing('informeSubmission');
        $informe = $this->informeSubmission;

        if ($informe && (int) $informe->reviewed_by === (int) $user->id) {
            return true;
        }

        if ($this->hasReviewInformAllPermission($user)) {
            return true;
        }

        if ((int) $this->decision_notification_supervisor_user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function activeJustificationStage(): ?DisciplinaryStage
    {
        return $this->stages()
            ->where('stage_type', StageType::JUSTIFICACION)
            ->open()
            ->orderByDesc('sequence')
            ->first();
    }

    /**
     * Evidencia PDF de notificación de citación cargada en el expediente.
     */
    public function latestCitationEvidenceDocument(): ?DisciplinaryDocument
    {
        $docs = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->orderByDesc('id')->get();

        $match = $docs->first(
            fn (DisciplinaryDocument $d) => $d->document_type === DocumentType::CITACION
                && str_contains((string) ($d->notes ?? ''), self::NOTE_CITATION_EVIDENCE_PREFIX)
        );

        return $match instanceof DisciplinaryDocument ? $match : null;
    }

    /** FO-GJ-03 generado y documento de citación asociado al expediente. */
    public function canReceiveCitationEvidence(): bool
    {
        if ($this->fo_gj_03_generated_at === null) {
            return false;
        }

        return $this->primaryFoGj03CitationDocument() !== null;
    }

    /**
     * Usuarios autorizados para cargar evidencia de citación (Etapa B).
     * Excluye planeación, abogados no titulares y operaciones/supervisores ajenos al caso.
     */
    public function canUserUploadCitationEvidence(User $user): bool
    {
        if (! $this->canReceiveCitationEvidence()) {
            return false;
        }

        if ($user->hasRole('planeacion')) {
            return false;
        }

        if ((int) $this->assigned_lawyer_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole('admin') || $user->hasPermissionTo('disciplinary.assign')) {
            return true;
        }

        $this->loadMissing('informeSubmission');
        $informe = $this->informeSubmission;

        if ($informe && (int) $informe->reviewed_by === (int) $user->id) {
            return true;
        }

        if ($this->hasReviewInformAllPermission($user)) {
            return true;
        }

        if ((int) $this->notification_supervisor_user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    private function hasReviewInformAllPermission(User $user): bool
    {
        return self::userCanReviewAllInformes($user);
    }
}
