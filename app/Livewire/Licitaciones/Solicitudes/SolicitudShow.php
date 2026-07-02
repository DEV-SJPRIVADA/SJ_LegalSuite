<?php

namespace App\Livewire\Licitaciones\Solicitudes;

use App\Enums\Licitaciones\RequestStatus;
use App\Models\Licitaciones\LicitacionComentario;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Services\Licitaciones\LicitacionDocumentService;
use App\Services\Licitaciones\LicitacionSolicitudService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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

    public function mount(LicitacionSolicitud $solicitud): void
    {
        Gate::authorize('view', $solicitud);
        $this->solicitud = $solicitud->load([
            'licitacion', 'usuarioResponsable', 'creador',
            'adjuntos.usuario', 'comentarios.usuario', 'historial.usuario',
        ]);
        $this->nuevoEstado = $solicitud->estado?->value ?? RequestStatus::Recibido->value;
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
        $this->solicitud->load('comentarios.usuario');
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

        $this->solicitud->load(['historial.usuario', 'comentarios.usuario']);
        $this->nuevoEstado = $this->solicitud->estado?->value ?? '';
        $this->reset('comentarioEstado');
        session()->flash('success', 'Estado actualizado.');
    }

    public function uploadAdjunto(LicitacionDocumentService $documents): void
    {
        Gate::authorize('uploadDocument', $this->solicitud);
        $this->validate(['nuevoAdjunto' => ['required', 'file', 'max:20480']]);
        $documents->uploadForSolicitud($this->solicitud, $this->nuevoAdjunto, auth()->user());
        $this->reset('nuevoAdjunto');
        $this->solicitud->load('adjuntos.usuario');
        session()->flash('success', 'Adjunto cargado.');
    }

    public function render()
    {
        return view('livewire.licitaciones.solicitudes.show', [
            'estados' => RequestStatus::cases(),
        ]);
    }
}
