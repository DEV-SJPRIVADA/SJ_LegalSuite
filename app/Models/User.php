<?php

namespace App\Models;

use App\Enums\UserArea;
use App\Models\Disciplinary\DisciplinaryCase;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'document_number',
        'phone',
        'area',
        'position',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'area' => UserArea::class,
            'is_active' => 'boolean',
        ];
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'assigned_lawyer_id');
    }

    public function reportedCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'reporter_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInArea(Builder $query, UserArea|string $area): Builder
    {
        return $query->where('area', $area instanceof UserArea ? $area->value : $area);
    }

    public function scopeLawyers(Builder $query): Builder
    {
        return $query->role('juridico');
    }
}
