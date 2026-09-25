<?php

namespace App\Livewire\Licitaciones\Solicitudes;

use App\Enums\Licitaciones\RequestStatus;
use App\Models\Licitaciones\LicitacionAdjunto;
use App\Models\Licitaciones\LicitacionComentario;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Services\Directory\CompanyDirectorySearchService;
use App\Services\Licitaciones\LicitacionDocumentService;
use App\Services\Licitaciones\LicitacionInvitadoService;
use App\Services\Licitaciones\LicitacionSolicitudService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Detalle solicitud')]
class SolicitudShow extends Component
{
    use WithFileUploads;

    public LicitacionSolicitud $solicitud;

    public string $nuevoComentario = '';

    public string $nuevoEstado = '';

    public string $comentarioEstado = '';

    /** @var mixed */
    public $nuevoAdjunto = null;

    public string $invitadosTexto = '';

    /** @var list<array{email: string, name: string|null}> */
    public array $aportantesSeleccionados = [];

    public string $aportanteBusqueda = '';

    public string $aportanteExterno = '';

    public string $mensajeInvitacion = '';

    public string $aportacionLimiteAt = '';

    public string $revisionComentario = '';

    public ?int $revisandoAdjuntoId = null;

    public string $emailNotificacionEdit = '';

    public function mount(LicitacionSolicitud $solicitud): void
    {
        Gate::authorize('view', $solicitud);
        $this->refreshSolicitud($solicitud);
        $this->nuevoEstado = $solicitud->estado?->value ?? RequestStatus::Recibido->value;
        $this->emailNotificacionEdit = (string) ($solicitud->email_notificacion ?: $solicitud->creador?->email ?: '');
        $this->aportacionLimiteAt = $this->formatAportacionLimiteInput(
            $solicitud->aportacionDeadline()
        );
    }

    public function guardarComentario(): void
    {
        Gate::authorize('comment', $this->solicitud);
        $this->validate(['nuevoComentario' => ['required', 'string', 'max:5000']]);

        LicitacionComentario::create([
            'solicitud_id' => $this->solicitud->id,
            'user_id' => auth()->id(),
            'comentario' => $this->nuevoComentario,
        ]);

        $this->reset('nuevoComentario');
        $this->refreshSolicitud();
        session()->flash('success', 'Comentario agregado.');
    }

    public function cambiarEstado(LicitacionSolicitudService $service): void
    {
        Gate::authorize('update', $this->solicitud);
        $this->validate([
            'nuevoEstado' => ['required', Rule::enum(RequestStatus::class)],
            'comentarioEstado' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->solicitud = $service->update(
            $this->solicitud,
            ['estado' => $this->nuevoEstado],
            auth()->user(),
            $this->comentarioEstado ?: null,
        );

        $this->refreshSolicitud();
        $this->nuevoEstado = $this->solicitud->estado?->value ?? '';
        $this->reset('comentarioEstado');
        session()->flash('success', 'Estado actualizado.');
    }

    public function uploadAdjunto(LicitacionDocumentService $documents): void
    {
        Gate::authorize('uploadDocument', $this->solicitud);
        $this->validate(
            ['nuevoAdjunto' => ['required', 'file', 'max:51200']],
            [
                'nuevoAdjunto.required' => 'Seleccione un archivo.',
                'nuevoAdjunto.max' => 'El archivo no puede superar los 50 MB.',
            ],
        );
        $documents->uploadForSolicitud($this->solicitud, $this->nuevoAdjunto, auth()->user());
        $this->reset('nuevoAdjunto');
        $this->refreshSolicitud();
        session()->flash('success', 'Adjunto cargado.');
    }

    public function guardarEmailNotificacion(): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);

        $data = $this->validate([
            'emailNotificacionEdit' => ['required', 'email', 'max:255'],
        ], [], [
            'emailNotificacionEdit' => 'correo para notificaciones',
        ]);

        $this->solicitud->update([
            'email_notificacion' => strtolower(trim($data['emailNotificacionEdit'])),
        ]);

        $this->refreshSolicitud();
        $this->emailNotificacionEdit = (string) $this->solicitud->email_notificacion;
        session()->flash('success', 'Correo de notificación actualizado.');
    }

    public function invitar(LicitacionInvitadoService $service): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);

        $this->validate([
            'aportantesSeleccionados' => ['required', 'array', 'min:1'],
            'aportantesSeleccionados.*.email' => ['required', 'email', 'max:255'],
            'aportantesSeleccionados.*.name' => ['nullable', 'string', 'max:255'],
            'mensajeInvitacion' => ['nullable', 'string', 'max:5000'],
            'aportacionLimiteAt' => ['required', 'date', 'after:now'],
        ], [
            'aportantesSeleccionados.required' => 'Seleccione al menos un aportante del directorio o agregue un correo.',
            'aportantesSeleccionados.min' => 'Seleccione al menos un aportante del directorio o agregue un correo.',
            'aportacionLimiteAt.required' => 'Indique la fecha y hora límite de entrega.',
            'aportacionLimiteAt.after' => 'El límite de entrega debe ser una fecha y hora futuras.',
        ], [
            'aportantesSeleccionados' => 'aportantes',
            'aportacionLimiteAt' => 'fecha y hora de límite de entrega',
        ]);

        $destinatarios = collect($this->aportantesSeleccionados)
            ->map(function (array $row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                $name = trim((string) ($row['name'] ?? ''));

                return [
                    'email' => $email,
                    'nombre' => $name !== '' ? $name : null,
                ];
            })
            ->filter()
            ->unique('email')
            ->values()
            ->all();

        if ($destinatarios === []) {
            $this->addError('aportantesSeleccionados', 'Indique al menos un correo válido.');

            return;
        }

        $service->inviteMany(
            $this->solicitud,
            $destinatarios,
            auth()->user(),
            $this->mensajeInvitacion ?: null,
            \Illuminate\Support\Carbon::parse($this->aportacionLimiteAt),
        );

        $this->reset('aportantesSeleccionados', 'aportanteBusqueda', 'aportanteExterno', 'mensajeInvitacion', 'invitadosTexto');
        $this->refreshSolicitud();
        $this->aportacionLimiteAt = $this->formatAportacionLimiteInput(
            $this->solicitud->aportacionDeadline()
        );
        session()->flash('success', 'Aportantes registrados. El correo de invitación se está enviando.');
    }

    public function agregarAportanteUsuario(string $email, string $name = ''): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);

        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('aportanteBusqueda', 'Correo no válido.');

            return;
        }

        $this->pushAportante($email, trim($name) !== '' ? trim($name) : null);
        $this->reset('aportanteBusqueda');
        $this->resetErrorBag('aportanteBusqueda', 'aportantesSeleccionados');
    }

    public function agregarAportanteExterno(): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);

        $email = strtolower(trim($this->aportanteExterno));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('aportanteExterno', 'Indique un correo válido.');

            return;
        }

        $this->pushAportante($email, null);
        $this->reset('aportanteExterno');
        $this->resetErrorBag('aportanteExterno', 'aportantesSeleccionados');
    }

    public function quitarAportante(string $email): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);
        $email = strtolower(trim($email));
        $this->aportantesSeleccionados = collect($this->aportantesSeleccionados)
            ->reject(fn (array $row) => strtolower((string) ($row['email'] ?? '')) === $email)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{id: string, name: string, email: string, source: string}>
     */
    #[Computed]
    public function aportanteResultados(): Collection
    {
        $selected = collect($this->aportantesSeleccionados)
            ->map(fn (array $row) => strtolower((string) ($row['email'] ?? '')))
            ->filter()
            ->all();

        return app(CompanyDirectorySearchService::class)
            ->search($this->aportanteBusqueda, $selected, 15);
    }

    private function pushAportante(string $email, ?string $name): void
    {
        $exists = collect($this->aportantesSeleccionados)
            ->contains(fn (array $row) => strtolower((string) ($row['email'] ?? '')) === $email);

        if ($exists) {
            return;
        }

        $this->aportantesSeleccionados[] = [
            'email' => $email,
            'name' => $name,
        ];
    }

    public function reenviarInvitacion(int $invitadoId, LicitacionInvitadoService $service): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);
        $invitado = $this->findInvitado($invitadoId);
        $service->resend($invitado, auth()->user());
        $this->refreshSolicitud();
        session()->flash('success', 'Invitación reenviada a '.$invitado->email.'.');
    }

    public function eliminarInvitado(int $invitadoId, LicitacionInvitadoService $service): void
    {
        Gate::authorize('manageInvitados', $this->solicitud);
        $service->remove($this->findInvitado($invitadoId), auth()->user());
        $this->refreshSolicitud();
        session()->flash('success', 'Aportante eliminado.');
    }

    public function abrirRechazo(int $adjuntoId): void
    {
        Gate::authorize('reviewDocument', $this->solicitud);
        $this->revisandoAdjuntoId = $adjuntoId;
        $this->revisionComentario = '';
    }

    public function cancelarRechazo(): void
    {
        $this->reset('revisandoAdjuntoId', 'revisionComentario');
    }

    public function aprobarDocumento(int $adjuntoId, LicitacionDocumentService $documents): void
    {
        Gate::authorize('reviewDocument', $this->solicitud);
        $adjunto = $documents->aprobar($this->findAdjunto($adjuntoId), auth()->user());
        $this->refreshSolicitud();
        $this->reset('revisandoAdjuntoId', 'revisionComentario');

        $email = $adjunto->invitado?->email;
        if ($adjunto->getAttribute('mail_notificado')) {
            session()->flash('success', 'Documento aprobado. Se envió correo a '.$email.'.');
        } else {
            session()->flash('success', 'Documento aprobado, pero no se pudo enviar el correo al aportante'.($email ? ' ('.$email.')' : '').'. Revise la configuración SMTP.');
        }
    }

    public function rechazarDocumento(LicitacionDocumentService $documents): void
    {
        Gate::authorize('reviewDocument', $this->solicitud);
        $this->validate([
            'revisandoAdjuntoId' => ['required', 'integer'],
            'revisionComentario' => ['required', 'string', 'max:5000'],
        ]);

        $adjunto = $documents->rechazar(
            $this->findAdjunto((int) $this->revisandoAdjuntoId),
            auth()->user(),
            $this->revisionComentario,
        );

        $this->refreshSolicitud();
        $this->reset('revisandoAdjuntoId', 'revisionComentario');

        $email = $adjunto->invitado?->email;
        if ($adjunto->getAttribute('mail_notificado')) {
            session()->flash('success', 'Corrección solicitada. Se envió correo a '.$email.'.');
        } else {
            session()->flash('success', 'Corrección solicitada, pero no se pudo enviar el correo al aportante'.($email ? ' ('.$email.')' : '').'.');
        }
    }

    public function reenviarResultadoRevision(int $adjuntoId, LicitacionDocumentService $documents): void
    {
        Gate::authorize('reviewDocument', $this->solicitud);
        $adjunto = $this->findAdjunto($adjuntoId);
        $ok = $documents->notificarResultadoRevision($adjunto);
        $email = $adjunto->invitado?->email ?? '—';

        session()->flash(
            'success',
            $ok
                ? 'Correo de resultado reenviado a '.$email.'.'
                : 'No se pudo reenviar el correo a '.$email.'. Revise SMTP / logs.'
        );
    }

    public function render()
    {
        return view('livewire.licitaciones.solicitudes.show', [
            'estados' => RequestStatus::cases(),
        ]);
    }

    private function refreshSolicitud(?LicitacionSolicitud $solicitud = null): void
    {
        $base = $solicitud ?? $this->solicitud;
        $this->solicitud = $base->fresh([
            'licitacion',
            'usuarioResponsable',
            'creador',
            'adjuntos.usuario',
            'adjuntos.invitado',
            'adjuntos.revisadoPor',
            'invitados.invitadoPor',
            'invitados.adjuntos',
            'comentarios.usuario',
            'historial.usuario',
        ]);
    }

    private function findInvitado(int $id): LicitacionSolicitudInvitado
    {
        return LicitacionSolicitudInvitado::query()
            ->where('solicitud_id', $this->solicitud->id)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function findAdjunto(int $id): LicitacionAdjunto
    {
        return LicitacionAdjunto::query()
            ->where('solicitud_id', $this->solicitud->id)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function formatAportacionLimiteInput(?\Illuminate\Support\Carbon $deadline): string
    {
        if ($deadline === null) {
            return now()->addDays(3)->setTime(17, 0)->format('Y-m-d\TH:i');
        }

        return $deadline->timezone(config('app.timezone'))->format('Y-m-d\TH:i');
    }
}
