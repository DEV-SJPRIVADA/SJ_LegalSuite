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
        'ultimo_recordatorio_at',
        'ultimo_acceso_at',
        'invitado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'invitado_at' => 'datetime',
            'notificado_at' => 'datetime',
            'ultimo_recordatorio_at' => 'datetime',
            'ultimo_acceso_at' => 'datetime',
        ];
    }

    public function hasUploadedDocuments(): bool
    {
        return $this->adjuntos()->exists();
    }

    /**
     * Ventana crítica: última hora antes del límite (y después, mientras no suban archivos).
     */
    public function isInFinalHourWindow(): bool
    {
        $deadline = $this->solicitud?->aportacionDeadline();
        if ($deadline === null) {
            return false;
        }

        return now()->gte($deadline->copy()->subHour());
    }

    public function needsAportacionReminder(): bool
    {
        if ($this->hasUploadedDocuments()) {
            return false;
        }

        if ($this->notificado_at === null) {
            return false;
        }

        $deadline = $this->solicitud?->aportacionDeadline();
        if ($deadline === null) {
            return false;
        }

        // Última hora (o ya vencido): recordatorio cada 10 minutos hasta que suban archivos.
        if ($this->isInFinalHourWindow()) {
            if ($this->ultimo_recordatorio_at === null) {
                return true;
            }

            return $this->ultimo_recordatorio_at->lte(now()->subMinutes(10));
        }

        // Antes de la última hora: desde 48 h antes, como máximo un recordatorio al día.
        if (now()->lt($deadline->copy()->subHours(48))) {
            return false;
        }

        if ($this->ultimo_recordatorio_at === null) {
            return true;
        }

        return $this->ultimo_recordatorio_at->lte(now()->subDay());
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
