<?php

namespace App\Models\Licitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Licitacion extends Model
{
    protected $table = 'licitaciones';

    protected $fillable = [
        'responsable_principal_id',
        'entidad_contratante',
        'modalidad_contratacion',
        'numero_proceso',
        'objeto',
        'cuantia',
        'plazo_ejecucion',
        'lugar_ejecucion',
        'medio_presentacion',
        'enlace_proceso',
        'participacion_tipo',
        'integrantes_participacion',
        'fecha_cierre_oferta',
        'hora_cierre_oferta',
        'fecha_observaciones_evaluacion',
        'fecha_adjudicacion',
        'cumplimos',
        'motivo_no_cumplir',
        'estado_proceso',
        'resultado',
        'adjudicado',
        'motivo_perdida',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cierre_oferta' => 'date',
            'fecha_observaciones_evaluacion' => 'date',
            'fecha_adjudicacion' => 'date',
        ];
    }

    public function responsablePrincipal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_principal_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(LicitacionSolicitud::class, 'licitacion_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(LicitacionAdjunto::class, 'licitacion_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($term)).'%';

        return $query->where(function ($q) use ($like) {
            $q->where('numero_proceso', 'like', $like)
                ->orWhere('entidad_contratante', 'like', $like)
                ->orWhere('objeto', 'like', $like)
                ->orWhere('estado_proceso', 'like', $like);
        });
    }
}
