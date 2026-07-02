<?php

namespace App\Models\Licitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicitacionComentario extends Model
{
    protected $table = 'licitacion_comentarios';

    protected $fillable = [
        'solicitud_id',
        'user_id',
        'comentario',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(LicitacionSolicitud::class, 'solicitud_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(LicitacionAdjunto::class, 'comentario_id');
    }
}
