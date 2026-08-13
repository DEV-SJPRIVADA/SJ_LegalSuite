<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\FaultSeverity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fault extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'severity',
        'description',
        'requires_extra_info',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'severity' => FaultSeverity::class,
            'requires_extra_info' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function disciplinaryCases(): BelongsToMany
    {
        return $this->belongsToMany(
            DisciplinaryCase::class,
            'disciplinary_case_fault',
        )->withPivot('extra_info')->withTimestamps();
    }

    public function citationTemplate(): HasOne
    {
        return $this->hasOne(FaultCitationTemplate::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
