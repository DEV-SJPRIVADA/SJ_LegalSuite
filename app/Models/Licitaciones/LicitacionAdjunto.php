<?php

namespace App\Models\Licitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicitacionAdjunto extends Model
{
    protected $table = 'licitacion_adjuntos';

    protected $fillable = [
        'solicitud_id',
        'licitacion_id',
        'comentario_id',
        'user_id',
        'nombre_archivo',
        'disk',
        'path',
        'mime_type',
        'tamaño_archivo',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(LicitacionSolicitud::class, 'solicitud_id');
    }

    public function licitacion(): BelongsTo
    {
        return $this->belongsTo(Licitacion::class, 'licitacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
