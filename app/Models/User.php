<?php

namespace App\Models;

use App\Enums\PlatformLevel;
use App\Enums\UserArea;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Licitaciones\Licitacion;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function authorizedMunicipalities(): BelongsToMany
    {
        return $this->belongsToMany(
            ColombianMunicipality::class,
            'user_authorized_municipalities',
            'user_id',
            'municipality_code',
            'id',
            'municipality_code',
        );
    }

    /**
     * Zona de supervisión (bandeja compartida). Un supervisor pertenece a una sola zona.
     */
    public function supervisionZones(): BelongsToMany
    {
        return $this->belongsToMany(
            Disciplinary\SupervisionZone::class,
            'supervision_zone_user',
        )->withTimestamps();
    }

    public function currentSupervisionZone(): ?Disciplinary\SupervisionZone
    {
        $this->loadMissing('supervisionZones');

        return $this->supervisionZones->first();
    }

    public function belongsToSupervisionZone(int $zoneId): bool
    {
        return $this->supervisionZones()->where('supervision_zones.id', $zoneId)->exists();
    }

    public function requiresFieldDisciplinaryScope(): bool
    {
        return app(\App\Support\Disciplinary\FieldDisciplinaryScopeService::class)
            ->requiresTerritorialScope($this);
    }

    public function hasFieldDisciplinaryScopeConfigured(): bool
    {
        return app(\App\Support\Disciplinary\FieldDisciplinaryScopeService::class)
            ->hasConfiguredScope($this);
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
        if ($this->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return false;
        }

        return $this->hasPlatformLevel(PlatformLevel::Nivel7, PlatformLevel::Nivel8);
    }

    /**
     * Revisor FO-GJ-51 / seguimiento Operaciones (nivel2): portal reducido tras autorizar.
     */
    public function isDisciplinaryOperacionesReviewer(): bool
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return false;
        }

        return $this->hasPlatformLevel(PlatformLevel::Nivel2);
    }

    /**
     * Programador de fechas: sólo solicitudes asignadas por dirección de planeación.
     */
    public function isDisciplinaryProgramador(): bool
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return false;
        }

        return $this->hasPlatformLevel(PlatformLevel::Nivel9);
    }

    /**
     * Etiqueta del único ítem del sidebar reducido (sin menú jurídico amplio).
     * Dirección de operaciones → «Diciplinarios»; campo/programador/etc. → «Informes».
     */
    public function minimalDisciplinarySidebarLabel(): string
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel7)) {
            return 'Supervisión';
        }

        if ($this->hasPlatformLevel(PlatformLevel::Nivel2)) {
            return 'Diciplinarios';
        }

        return 'Informes';
    }

    /** Punto de entrada del módulo disciplinario según rol y permisos. */
    public function disciplinaryPortalUrl(): string
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel3)) {
            return route('disciplinary.coordinations.index');
        }

        if ($this->hasPlatformLevel(PlatformLevel::Nivel7)) {
            return route('disciplinary.evidences-pending.index');
        }

        if ($this->can('viewDashboard', DisciplinaryCase::class)) {
            return route('disciplinary.dashboard');
        }

        if ($this->can('viewAny', DisciplinaryCase::class)) {
            return route('disciplinary.cases.index');
        }

        return $this->suiteLandingUrl();
    }

    /**
     * Tablero de inicio (command center): solo rol administrador del suite.
     */
    public function canViewHomeCommandCenter(): bool
    {
        return $this->hasPlatformLevel(PlatformLevel::Nivel1);
    }

    /**
     * Destino por defecto al ingresar al suite (login, logo, fallback de rutas).
     */
    public function suiteLandingUrl(): string
    {
        if ($this->canViewHomeCommandCenter()) {
            return route('dashboard');
        }

        if ($this->hasDisciplinaryPortalAccess()) {
            return $this->disciplinaryPortalUrl();
        }

        if ($this->can('viewAny', \App\Models\Employee::class)) {
            return route('employees.index');
        }

        if ($this->can('viewAny', User::class)) {
            return route('users.index');
        }

        return route('profile');
    }

    /**
     * Enlace del sub-nav «Disciplinarios» → listado de expedientes (no el tablero).
     * La entrada al módulo en el sidebar usa {@see disciplinaryPortalUrl()}.
     */
    public function disciplinaryCasesNavUrl(): string
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel6, PlatformLevel::Nivel5)) {
            return route('disciplinary.cases.index');
        }

        if ($this->can('viewAny', DisciplinaryCase::class) && ! $this->hasPlatformLevel(PlatformLevel::Nivel3)) {
            return route('disciplinary.cases.index');
        }

        return $this->disciplinaryPortalUrl();
    }

    public function hasDisciplinaryPortalAccess(): bool
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel3, PlatformLevel::Nivel7)) {
            return true;
        }

        return $this->can('viewDashboard', DisciplinaryCase::class)
            || $this->can('viewAny', DisciplinaryCase::class);
    }

    public function licitacionesPortalUrl(): string
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel1, PlatformLevel::Nivel5, PlatformLevel::Nivel6)
            || $this->can('viewDashboard', Licitacion::class)) {
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
        if ($this->hasPlatformLevel(
            PlatformLevel::Nivel1,
            PlatformLevel::Nivel5,
            PlatformLevel::Nivel6,
        )) {
            return true;
        }

        return $this->can('viewDashboard', Licitacion::class)
            || $this->can('viewAny', Licitacion::class)
            || $this->can('viewAny', \App\Models\Licitaciones\LicitacionSolicitud::class);
    }

    /**
     * Sidebar global del suite: gerencia (admin), dirección jurídica (abogado) y auditoría ven todos los módulos.
     */
    public function canSeeFullAppSidebar(): bool
    {
        return $this->hasPlatformLevel(
            PlatformLevel::Nivel1,
            PlatformLevel::Nivel5,
            PlatformLevel::Nivel6,
        );
    }

    /**
     * Detecta nivel de plataforma por nombre actual, nombre legado (admin, abogado…) o level_number.
     * Evita hasRole() de Spatie tras el rename, que puede devolver false si la caché aún tiene el slug viejo.
     */
    public function hasPlatformLevel(PlatformLevel ...$levels): bool
    {
        if ($levels === []) {
            return false;
        }

        $wanted = [];
        foreach ($levels as $level) {
            $wanted[$level->value] = true;
            $wanted[$level->number()] = true;
        }

        foreach (PlatformLevel::legacyMap() as $legacy => $slug) {
            if (isset($wanted[$slug])) {
                $wanted[$legacy] = true;
            }
        }

        $this->loadMissing('roles');

        foreach ($this->roles as $role) {
            if (isset($wanted[$role->name])) {
                return true;
            }

            if ($role->level_number && isset($wanted[(int) $role->level_number])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nombres de rol Spatie presentes en BD para esos niveles (slug actual + legado).
     *
     * @return list<string>
     */
    public static function existingRoleNamesForLevels(PlatformLevel ...$levels): array
    {
        $names = [];
        foreach ($levels as $level) {
            $names[] = $level->value;
            foreach (PlatformLevel::legacyMap() as $legacy => $slug) {
                if ($slug === $level->value) {
                    $names[] = $legacy;
                }
            }
        }

        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_values(array_unique($names)))
            ->pluck('name')
            ->all();
    }

    /**
     * Usuarios con alguno de los niveles (acepta slugs actuales y nombres legado si aún existen).
     *
     * @param  PlatformLevel  ...$levels
     */
    public static function queryByPlatformLevels(PlatformLevel ...$levels)
    {
        return static::constrainByPlatformLevels(static::query(), ...$levels);
    }

    /**
     * Aplica el filtro de niveles a una query existente sin lanzar RoleDoesNotExist.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public static function constrainByPlatformLevels(Builder $query, PlatformLevel ...$levels): Builder
    {
        $existing = static::existingRoleNamesForLevels(...$levels);

        if ($existing === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->role($existing);
    }

    /**
     * Portal reducido: sin tablero general ni otros módulos; sólo asignaciones disciplinarias.
     */
    public function isMinimalDisciplinaryPortalUser(): bool
    {
        if ($this->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return false;
        }

        return $this->hasPlatformLevel(PlatformLevel::Nivel7, PlatformLevel::Nivel8, PlatformLevel::Nivel9);
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
        return static::constrainByPlatformLevels($query, PlatformLevel::Nivel6);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2), 'UTF-8');
        }

        return mb_strtoupper(
            mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1),
            'UTF-8'
        );
    }

    public function isPlatformAdmin(): bool
    {
        return $this->hasPlatformLevel(PlatformLevel::Nivel1);
    }

    public function cargoDisplayLabel(): string
    {
        if ($this->isPlatformAdmin()) {
            return 'Admin plataforma';
        }

        if ($this->relationLoaded('jobPosition') && $this->jobPosition) {
            return (string) $this->jobPosition->name;
        }

        if ($this->job_position_id) {
            $this->loadMissing('jobPosition');
            if ($this->jobPosition?->name) {
                return (string) $this->jobPosition->name;
            }
        }

        return (string) ($this->position ?: '—');
    }

    public function primaryRoleLabel(): ?string
    {
        $roleName = $this->roles->first()?->name;

        if ($roleName === null) {
            return null;
        }

        $level = \App\Enums\PlatformLevel::tryFrom($roleName);

        return $level?->title().' — '.$level->subtitle();
    }
}
