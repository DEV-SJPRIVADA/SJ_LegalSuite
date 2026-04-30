<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\StageStatus;
use App\Enums\Disciplinary\StageType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaryStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'disciplinary_case_id',
        'stage_type',
        'form_code',
        'status',
        'scheduled_at',
        'performed_at',
        'completed_at',
        'deadline_at',
        'performed_by',
        'notes',
        'metadata',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'stage_type' => StageType::class,
            'status' => StageStatus::class,
            'scheduled_at' => 'datetime',
            'performed_at' => 'datetime',
            'completed_at' => 'datetime',
            'deadline_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DisciplinaryDocument::class);
    }

    public function scopeOfType(Builder $query, StageType|string $type): Builder
    {
        return $query->where('stage_type', $type instanceof StageType ? $type->value : $type);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [StageStatus::PENDIENTE->value, StageStatus::EN_CURSO->value]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('deadline_at')->whereDate('deadline_at', '<', now());
    }
}
