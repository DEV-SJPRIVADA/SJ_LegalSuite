<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\ColombianMunicipality;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        'personnel_id',
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
        ];
    }

    // ---------- Relaciones ----------

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
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
     * Etapa A → B: exige respuesta de planeación en el hilo de agenda (primera intervención del lado planeación).
     */
    public function hasAgendaPlanningReply(): bool
    {
        return $this->agendaThread?->hasPlanningReply() ?? false;
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
                ->orWhereHas('personnel', fn (Builder $p) => $p->search(trim((string) $like, '%')));
        });
    }

    /**
     * Planeación: expedientes en etapa Informe donde el abogado titular ya escribió al menos un mensaje en el hilo de agenda.
     * Si el caso sale de Informe, deja de aparecer en el listado de planeación.
     */
    public function scopeForPlaneacionInbox(Builder $query): Builder
    {
        return $query
            ->where('disciplinary_cases.current_status', CaseStatus::INFORME->value)
            ->whereNotNull('disciplinary_cases.assigned_lawyer_id')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw('1'))
                    ->from('disciplinary_agenda_messages as dam')
                    ->join('disciplinary_agenda_threads as dat', 'dam.thread_id', '=', 'dat.id')
                    ->whereColumn('dat.disciplinary_case_id', 'disciplinary_cases.id')
                    ->whereColumn('dam.user_id', 'disciplinary_cases.assigned_lawyer_id');
            });
    }

    public function isVisibleToPlaneacionUser(): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->forPlaneacionInbox()
            ->exists();
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
            return $query->where('assigned_lawyer_id', $user->id);
        }

        if ($user->hasAnyRole(['supervisor', 'operador'])) {
            return $query->where('current_status', '!=', CaseStatus::BORRADOR->value);
        }

        if ($user->hasRole('programador')) {
            return $query->where('current_status', '!=', CaseStatus::BORRADOR->value);
        }

        if ($user->hasRole('planeacion')) {
            return $query->forPlaneacionInbox();
        }

        return $query;
    }

    // ---------- Helpers de dominio ----------

    public function bucket(): CaseBucket
    {
        return $this->current_status->bucket();
    }

    /**
     * Pool de campo (supervisor / operador por turno): no hay titular fijo; se excluye borrador interno.
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
}
