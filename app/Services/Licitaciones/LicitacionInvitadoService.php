<?php

namespace App\Services\Licitaciones;

use App\Jobs\Licitaciones\SendLicitacionInvitacionJob;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicitacionInvitadoService
{
    public function __construct(
        private readonly LicitacionHistorialService $historial,
    ) {}

    /**
     * @param  list<array{email: string, nombre?: string|null}>  $destinatarios
     * @return list<LicitacionSolicitudInvitado>
     */
    public function inviteMany(
        LicitacionSolicitud $solicitud,
        array $destinatarios,
        User $actor,
        ?string $mensaje = null,
    ): array {
        $created = [];

        DB::transaction(function () use ($solicitud, $destinatarios, $actor, $mensaje, &$created) {
            foreach ($destinatarios as $row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                if ($email === '') {
                    continue;
                }

                $existing = LicitacionSolicitudInvitado::query()
                    ->where('solicitud_id', $solicitud->id)
                    ->where('email', $email)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'nombre' => $row['nombre'] ?? $existing->nombre,
                        'mensaje' => $mensaje ?? $existing->mensaje,
                    ]);
                    $created[] = ['invitado' => $existing->fresh(), 'reenvio' => true];

                    continue;
                }

                $invitado = LicitacionSolicitudInvitado::create([
                    'solicitud_id' => $solicitud->id,
                    'email' => $email,
                    'nombre' => $row['nombre'] ?? null,
                    'token' => LicitacionSolicitudInvitado::generateToken(),
                    'mensaje' => $mensaje,
                    'invitado_at' => now(),
                    'invitado_por_id' => $actor->id,
                ]);

                $created[] = ['invitado' => $invitado, 'reenvio' => false];
            }

            if ($created === []) {
                throw ValidationException::withMessages([
                    'emails' => 'Indique al menos un correo válido.',
                ]);
            }

            $this->historial->log($solicitud, $actor, 'invitados_agregados', [
                'emails' => array_map(fn (array $row) => $row['invitado']->email, $created),
            ]);
        });

        $invitados = [];
        foreach ($created as $row) {
            $this->queueInvitation($row['invitado'], $row['reenvio']);
            $invitados[] = $row['invitado'];
        }

        return $invitados;
    }

    public function resend(LicitacionSolicitudInvitado $invitado, User $actor): void
    {
        $this->queueInvitation($invitado, true);
        $this->historial->log($invitado->solicitud, $actor, 'invitacion_reenviada', [
            'email' => $invitado->email,
        ]);
    }

    public function remove(LicitacionSolicitudInvitado $invitado, User $actor): void
    {
        if ($invitado->adjuntos()->exists()) {
            throw ValidationException::withMessages([
                'invitado' => 'No se puede eliminar: el aportante ya subió documentos.',
            ]);
        }

        $email = $invitado->email;
        $solicitud = $invitado->solicitud;
        $invitado->delete();

        $this->historial->log($solicitud, $actor, 'invitado_eliminado', ['email' => $email]);
    }

    private function queueInvitation(LicitacionSolicitudInvitado $invitado, bool $reenvio): void
    {
        $invitadoId = $invitado->id;

        // notificado_at se marca al enviar el correo con éxito (en el Job).
        $invitado->update([
            'invitado_at' => $invitado->invitado_at ?? now(),
        ]);

        dispatch(function () use ($invitadoId, $reenvio) {
            SendLicitacionInvitacionJob::dispatchSync($invitadoId, $reenvio);
        })->afterResponse();
    }
}
