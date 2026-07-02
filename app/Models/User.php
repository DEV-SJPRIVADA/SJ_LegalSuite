<?php

namespace App\Models;

use App\Enums\UserArea;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Licitaciones\Licitacion;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'organizational_area_id',
        'job_position_id',
        'position',
        'is_active',
        'read_only',
        'must_change_password',
        'theme',
        'signature_path',
        'signature_disk',
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
            'area' => 'string',
            'is_active' => 'boolean',
            'read_only' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function organizationalArea(): BelongsTo
    {
        return $this->belongsTo(OrganizationalArea::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
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

    public function hasSignature(): bool
    {
        return filled($this->signature_path);
    }

    public function displayJobTitle(): string
    {
        $this->loadMissing('jobPosition');

        if ($this->jobPosition?->name) {
            return (string) $this->jobPosition->name;
        }

        return filled($this->position) ? (string) $this->position : 'Analista de Relaciones Laborales';
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
     * Etiqueta del único ítem del sidebar reducido (sin menú jurídico amplio).
     * Roles «director» u «operaciones» → «Diciplinarios» (nombre del módulo); campo/programador/etc. → «Informes».
     */
    public function minimalDisciplinarySidebarLabel(): string
    {
        if ($this->hasAnyRole(['director', 'operaciones'])) {
            return 'Diciplinarios';
        }

        return 'Informes';
    }

    /** Punto de entrada del módulo disciplinario según rol y permisos. */
    public function disciplinaryPortalUrl(): string
    {
        if ($this->hasRole('planeacion')) {
            return route('disciplinary.coordinations.index');
        }

        if ($this->hasRole('supervisor')) {
            return route('disciplinary.evidences-pending.index');
        }

        if ($this->can('viewDashboard', DisciplinaryCase::class)) {
            return route('disciplinary.dashboard');
        }

        if ($this->can('viewAny', DisciplinaryCase::class)) {
            return route('disciplinary.cases.index');
        }

        return route('dashboard');
    }

    /**
     * Enlace del menú «Disciplinarios» / listado de expedientes (no el tablero).
     * Abogado y auditor: listado; el tablero sigue en {@see disciplinaryPortalUrl()} al ingresar.
     */
    public function disciplinaryCasesNavUrl(): string
    {
        if ($this->hasAnyRole(['abogado', 'auditor'])) {
            return route('disciplinary.cases.index');
        }

        if ($this->can('viewAny', DisciplinaryCase::class) && ! $this->hasRole('planeacion')) {
            return route('disciplinary.cases.index');
        }

        return $this->disciplinaryPortalUrl();
    }

    public function hasDisciplinaryPortalAccess(): bool
    {
        if ($this->hasAnyRole(['planeacion', 'supervisor'])) {
            return true;
        }

        return $this->can('viewDashboard', DisciplinaryCase::class)
            || $this->can('viewAny', DisciplinaryCase::class);
    }

    public function licitacionesPortalUrl(): string
    {
        if ($this->can('viewDashboard', Licitacion::class)) {
            return route('licitaciones.dashboard');
        }

        if ($this->can('viewAny', Licitacion::class)) {
            return route('licitaciones.procesos.index');
        }

        if ($this->can('viewAny', \App\Models\Licitaciones\LicitacionSolicitud::class)) {
            return route('licitaciones.solicitudes.index');
        }

        return route('dashboard');
    }

    public function hasLicitacionesPortalAccess(): bool
    {
        return $this->can('viewDashboard', Licitacion::class)
            || $this->can('viewAny', Licitacion::class)
            || $this->can('viewAny', \App\Models\Licitaciones\LicitacionSolicitud::class);
    }

    /**
     * Sidebar global del suite: gerencia (admin), dirección jurídica (abogado) y auditoría ven todos los módulos.
     */
    public function canSeeFullAppSidebar(): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->hasAnyRole(['abogado', 'auditor']);
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

    public function scopeInArea(Builder $query, UserArea|string|null $area): Builder
    {
        if ($area instanceof UserArea) {
            return $query->where('area', $area->value);
        }

        return $query->where('area', (string) $area);
    }

    /**
     * Etiqueta del área: catálogo organizacional si existe; si no, enum legado por columna `area`.
     */
    public function areaDisplayLabel(): ?string
    {
        if ($this->relationLoaded('organizationalArea')) {
            $org = $this->organizationalArea;
        } else {
            $org = $this->organizationalArea()->first();
        }

        if ($org instanceof OrganizationalArea) {
            return $org->name;
        }

        $slug = $this->area;

        return UserArea::tryFrom((string) $slug)?->label();
    }

    public function scopeLawyers(Builder $query): Builder
    {
        return $query->role('abogado');
    }
}
