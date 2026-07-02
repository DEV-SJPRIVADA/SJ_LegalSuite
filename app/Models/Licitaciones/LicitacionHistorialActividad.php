<?php

namespace App\Models\Licitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicitacionHistorialActividad extends Model
{
    protected $table = 'licitacion_historial_actividades';

    public $timestamps = false;

    protected $fillable = [
        'solicitud_id',
        'user_id',
        'accion',
        'detalles',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'detalles' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(LicitacionSolicitud::class, 'solicitud_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
