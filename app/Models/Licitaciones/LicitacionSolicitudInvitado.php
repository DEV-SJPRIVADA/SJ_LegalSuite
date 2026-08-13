<?php

namespace App\Models\Licitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LicitacionSolicitudInvitado extends Model
{
    protected $table = 'licitacion_solicitud_invitados';

    protected $fillable = [
        'solicitud_id',
        'email',
        'nombre',
        'token',
        'mensaje',
        'invitado_at',
        'notificado_at',
        'ultimo_acceso_at',
        'invitado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'invitado_at' => 'datetime',
            'notificado_at' => 'datetime',
            'ultimo_acceso_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(LicitacionSolicitud::class, 'solicitud_id');
    }

    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitado_por_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(LicitacionAdjunto::class, 'invitado_id');
    }

    public function portalUrl(): string
    {
        return route('licitaciones.aportacion', $this->token);
    }
}
