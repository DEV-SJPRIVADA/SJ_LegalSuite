<?php

namespace App\Models\Disciplinary;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisionZone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'notification_email',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supervision_zone_user')
            ->withTimestamps();
    }

    public function citationCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'notification_supervision_zone_id');
    }

    public function decisionCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'decision_notification_supervision_zone_id');
    }

    /** @param Builder<SupervisionZone> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function displayLabel(): string
    {
        $name = trim((string) $this->name);
        if (filled($this->code)) {
            return $name.' ('.$this->code.')';
        }

        return $name;
    }
}
