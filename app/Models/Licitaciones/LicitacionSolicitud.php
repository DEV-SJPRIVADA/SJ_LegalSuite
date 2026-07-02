<?php

namespace App\Models\Licitaciones;

use App\Enums\Licitaciones\Periodicity;
use App\Enums\Licitaciones\PetitionType;
use App\Enums\Licitaciones\RequestStatus;
use App\Enums\Licitaciones\RequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicitacionSolicitud extends Model
{
    protected $table = 'licitacion_solicitudes';

    protected $fillable = [
        'licitacion_id',
        'numero_radicado',
        'fecha_creacion',
        'nombre',
        'descripcion',
        'area_responsable',
        'usuario_responsable_id',
        'tipo_solicitud',
        'periodicidad',
        'tipo_peticion',
        'fecha_limite',
        'estado',
        'created_by_id',
        'archivo_adjunto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'date',
            'fecha_limite' => 'date',
            'tipo_solicitud' => RequestType::class,
            'periodicidad' => Periodicity::class,
            'tipo_peticion' => PetitionType::class,
            'estado' => RequestStatus::class,
        ];
    }

    public function licitacion(): BelongsTo
    {
        return $this->belongsTo(Licitacion::class, 'licitacion_id');
    }

    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_responsable_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(LicitacionAdjunto::class, 'solicitud_id');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(LicitacionComentario::class, 'solicitud_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(LicitacionHistorialActividad::class, 'solicitud_id');
    }

    public function scopeForActor(Builder $query, User $user): Builder
    {
        if ($user->can('manageSolicitudes', Licitacion::class)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('usuario_responsable_id', $user->id)
                ->orWhere('created_by_id', $user->id);
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($term)).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('numero_radicado', 'like', $like)
                ->orWhere('nombre', 'like', $like)
                ->orWhere('descripcion', 'like', $like)
                ->orWhere('area_responsable', 'like', $like)
                ->orWhere('estado', 'like', $like);
        });
    }
}
