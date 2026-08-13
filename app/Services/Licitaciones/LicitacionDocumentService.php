<?php

namespace App\Services\Licitaciones;

use App\Enums\Licitaciones\DocumentRevisionStatus;
use App\Jobs\Licitaciones\NotifyDocumentoAportadoJob;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionAdjunto;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Models\User;
use App\Notifications\Licitaciones\DocumentoAportadoNotification;
use App\Notifications\Licitaciones\DocumentoRevisionResultadoNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LicitacionDocumentService
{
    public function __construct(
        private readonly LicitacionHistorialService $historial,
    ) {}

    public function uploadForSolicitud(LicitacionSolicitud $solicitud, UploadedFile $file, User $actor): LicitacionAdjunto
    {
        return DB::transaction(function () use ($solicitud, $file, $actor) {
            $path = $file->store('licitaciones/solicitudes/'.$solicitud->id, 'local');

            $adjunto = LicitacionAdjunto::create([
                'solicitud_id' => $solicitud->id,
                'licitacion_id' => $solicitud->licitacion_id,
                'user_id' => $actor->id,
                'nombre_archivo' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamaño_archivo' => $file->getSize(),
                'revision_estado' => DocumentRevisionStatus::Aprobado,
            ]);

            $this->historial->log($solicitud, $actor, 'documento_interno_subido', [
                'adjunto_id' => $adjunto->id,
                'archivo' => $adjunto->nombre_archivo,
            ]);

            return $adjunto;
        });
    }

    public function uploadForLicitacion(Licitacion $licitacion, UploadedFile $file, User $actor): LicitacionAdjunto
    {
        return DB::transaction(function () use ($licitacion, $file, $actor) {
            $path = $file->store('licitaciones/'.$licitacion->id, 'local');

            return LicitacionAdjunto::create([
                'licitacion_id' => $licitacion->id,
                'user_id' => $actor->id,
                'nombre_archivo' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamaño_archivo' => $file->getSize(),
                'revision_estado' => DocumentRevisionStatus::Aprobado,
            ]);
        });
    }

    public function uploadFromInvitado(
        LicitacionSolicitudInvitado $invitado,
        UploadedFile $file,
        ?LicitacionAdjunto $reemplaza = null,
    ): LicitacionAdjunto {
        $solicitud = $invitado->solicitud()->with(['creador', 'usuarioResponsable', 'licitacion'])->firstOrFail();

        if ($reemplaza) {
            if ($reemplaza->invitado_id !== $invitado->id) {
                throw ValidationException::withMessages([
                    'archivo' => 'No puede reemplazar un documento de otro aportante.',
                ]);
            }
            if ($reemplaza->revision_estado !== DocumentRevisionStatus::Rechazado) {
                throw ValidationException::withMessages([
                    'archivo' => 'Solo puede reemplazar documentos que requieren corrección.',
                ]);
            }
        }

        $adjunto = DB::transaction(function () use ($invitado, $file, $reemplaza, $solicitud) {
            if ($reemplaza) {
                $reemplaza->update([
                    'revision_estado' => DocumentRevisionStatus::Reemplazado,
                ]);
            }

            $path = $file->store('licitaciones/solicitudes/'.$solicitud->id.'/aportantes/'.$invitado->id, 'local');

            $adjunto = LicitacionAdjunto::create([
                'solicitud_id' => $solicitud->id,
                'licitacion_id' => $solicitud->licitacion_id,
                'user_id' => null,
                'invitado_id' => $invitado->id,
                'uploader_email' => $invitado->email,
                'nombre_archivo' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamaño_archivo' => $file->getSize(),
                'revision_estado' => DocumentRevisionStatus::Pendiente,
                'reemplaza_adjunto_id' => $reemplaza?->id,
            ]);

            $invitado->update(['ultimo_acceso_at' => now()]);

            $this->historial->log($solicitud, null, 'documento_aportado', [
                'adjunto_id' => $adjunto->id,
                'archivo' => $adjunto->nombre_archivo,
                'email' => $invitado->email,
            ]);

            return $adjunto;
        });

        // Tras la respuesta HTTP (ver AppServiceProvider::fastcgi_finish_request).
        dispatch(function () use ($solicitud, $adjunto) {
            NotifyDocumentoAportadoJob::dispatchSync($solicitud->id, $adjunto->id);
        })->afterResponse();

        return $adjunto;
    }

    public function notifyDocumentoAportado(LicitacionSolicitud $solicitud, LicitacionAdjunto $adjunto): void
    {
        $solicitud->loadMissing(['creador', 'usuarioResponsable']);
        $adjunto->loadMissing(['solicitud', 'invitado']);

        $usersById = collect([
            $solicitud->creador,
            $solicitud->usuarioResponsable,
        ])->filter()->keyBy('id');

        if ($solicitud->email_notificacion) {
            $extra = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower(trim($solicitud->email_notificacion))])
                ->first();
            if ($extra) {
                $usersById->put($extra->id, $extra);
            }
        }

        foreach ($usersById as $user) {
            try {
                $user->notify(new DocumentoAportadoNotification($adjunto));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $emails = collect([
            $solicitud->email_notificacion,
            $solicitud->creador?->email,
            $solicitud->usuarioResponsable?->email,
        ])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim($email)))
            ->unique();

        $userEmails = $usersById->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim($email)));

        foreach ($emails->diff($userEmails) as $email) {
            try {
                Notification::route('mail', $email)
                    ->notify(new DocumentoAportadoNotification($adjunto));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function aprobar(LicitacionAdjunto $adjunto, User $revisor): LicitacionAdjunto
    {
        return $this->revisar($adjunto, $revisor, DocumentRevisionStatus::Aprobado, null);
    }

    public function rechazar(LicitacionAdjunto $adjunto, User $revisor, string $comentario): LicitacionAdjunto
    {
        $comentario = trim($comentario);
        if ($comentario === '') {
            throw ValidationException::withMessages([
                'revision_comentario' => 'Indique qué debe corregir el aportante.',
            ]);
        }

        return $this->revisar($adjunto, $revisor, DocumentRevisionStatus::Rechazado, $comentario);
    }

    /**
     * Reenvía el correo de resultado de revisión al aportante.
     */
    public function notificarResultadoRevision(LicitacionAdjunto $adjunto): bool
    {
        $adjunto->loadMissing(['solicitud.licitacion', 'invitado']);

        if (! $adjunto->invitado?->email) {
            return false;
        }

        if (! in_array($adjunto->revision_estado, [DocumentRevisionStatus::Aprobado, DocumentRevisionStatus::Rechazado], true)) {
            return false;
        }

        try {
            Notification::route('mail', $adjunto->invitado->email)
                ->notify(new DocumentoRevisionResultadoNotification($adjunto));

            return true;
        } catch (\Throwable $e) {
            report($e);
            Log::error('licitaciones.notify_revision_failed', [
                'adjunto_id' => $adjunto->id,
                'email' => $adjunto->invitado->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function revisar(
        LicitacionAdjunto $adjunto,
        User $revisor,
        DocumentRevisionStatus $estado,
        ?string $comentario,
    ): LicitacionAdjunto {
        if ($adjunto->invitado_id === null) {
            throw ValidationException::withMessages([
                'adjunto' => 'Solo se revisan documentos aportados por invitados.',
            ]);
        }

        if ($adjunto->revision_estado !== DocumentRevisionStatus::Pendiente) {
            throw ValidationException::withMessages([
                'adjunto' => 'Este documento ya fue revisado.',
            ]);
        }

        $fresh = DB::transaction(function () use ($adjunto, $revisor, $estado, $comentario) {
            $adjunto->update([
                'revision_estado' => $estado,
                'revision_comentario' => $comentario,
                'revisado_por_id' => $revisor->id,
                'revisado_at' => now(),
            ]);

            $adjunto->load(['solicitud', 'invitado']);

            $this->historial->log($adjunto->solicitud, $revisor, 'documento_revisado', [
                'adjunto_id' => $adjunto->id,
                'estado' => $estado->value,
                'comentario' => $comentario,
            ]);

            return $adjunto->fresh(['usuario', 'invitado', 'revisadoPor']);
        });

        // Síncrono: la revisión debe asegurar el correo al aportante (no afterResponse).
        $enviado = $this->notificarResultadoRevision($fresh);
        $fresh->setAttribute('mail_notificado', $enviado);

        return $fresh;
    }

    public function delete(LicitacionAdjunto $adjunto): void
    {
        if ($adjunto->path && Storage::disk($adjunto->disk)->exists($adjunto->path)) {
            Storage::disk($adjunto->disk)->delete($adjunto->path);
        }

        $adjunto->delete();
    }
}
