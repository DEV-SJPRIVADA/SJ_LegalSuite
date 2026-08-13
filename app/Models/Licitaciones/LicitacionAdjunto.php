<?php

namespace App\Models\Licitaciones;

use App\Enums\Licitaciones\DocumentRevisionStatus;
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
        'invitado_id',
        'uploader_email',
        'revision_estado',
        'revision_comentario',
        'revisado_por_id',
        'revisado_at',
        'reemplaza_adjunto_id',
        'nombre_archivo',
        'disk',
        'path',
        'mime_type',
        'tamaño_archivo',
    ];

    protected function casts(): array
    {
        return [
            'revision_estado' => DocumentRevisionStatus::class,
            'revisado_at' => 'datetime',
        ];
    }

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

    public function invitado(): BelongsTo
    {
        return $this->belongsTo(LicitacionSolicitudInvitado::class, 'invitado_id');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por_id');
    }

    public function reemplaza(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reemplaza_adjunto_id');
    }

    public function uploaderLabel(): string
    {
        if ($this->usuario) {
            return $this->usuario->name;
        }

        if ($this->invitado?->nombre) {
            return $this->invitado->nombre.' ('.$this->uploader_email.')';
        }

        return $this->uploader_email ?: 'Aportante externo';
    }
}
