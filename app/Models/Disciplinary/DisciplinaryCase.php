<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\StageType;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'city',
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

    public function scopeInCity(Builder $query, string $city): Builder
    {
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

    // ---------- Helpers de dominio ----------

    public function bucket(): CaseBucket
    {
        return $this->current_status->bucket();
    }

    public function isFinalized(): bool
    {
        return $this->current_status->isTerminal();
    }
}
