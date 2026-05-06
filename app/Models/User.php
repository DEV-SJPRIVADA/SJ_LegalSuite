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
        'read_only',
        'must_change_password',
        'theme',
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
            'read_only' => 'boolean',
            'must_change_password' => 'boolean',
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

    public function assignedOperatorCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'assigned_operator_id');
    }

    public function assignedPlannerCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class, 'assigned_planner_id');
    }

    /**
     * Perfil de campo (supervisor / operador): sólo trabajo asignado por dirección de operaciones.
     */
    public function isDisciplinaryFieldOperator(): bool
    {
        if ($this->hasRole('admin')) {
            return false;
        }

        return $this->hasAnyRole(['supervisor', 'operador']);
    }

    /**
     * Programador de fechas: sólo solicitudes asignadas por dirección de planeación.
     */
    public function isDisciplinaryProgramador(): bool
    {
        if ($this->hasRole('admin')) {
            return false;
        }

        return $this->hasRole('programador');
    }

    /**
     * Portal reducido: sin tablero general ni otros módulos; sólo asignaciones disciplinarias.
     */
    public function isMinimalDisciplinaryPortalUser(): bool
    {
        if ($this->hasRole('admin')) {
            return false;
        }

        return $this->hasAnyRole(['supervisor', 'operador', 'programador']);
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
        return $query->role('abogado');
    }
}
