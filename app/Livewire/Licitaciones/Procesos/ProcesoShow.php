<?php

namespace App\Livewire\Licitaciones\Procesos;

use App\Models\Licitaciones\Licitacion;
use App\Services\Licitaciones\LicitacionDocumentService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Detalle licitación')]
class ProcesoShow extends Component
{
    use WithFileUploads;

    public Licitacion $licitacion;

    /** @var mixed */
    public $nuevoAdjunto = null;

    public function mount(Licitacion $licitacion): void
    {
        Gate::authorize('view', $licitacion);
        $this->licitacion = $licitacion->load(['responsablePrincipal', 'solicitudes.usuarioResponsable', 'adjuntos.usuario']);
    }

    public function uploadAdjunto(LicitacionDocumentService $documents): void
    {
        Gate::authorize('uploadDocument', $this->licitacion);
        $this->validate(
            ['nuevoAdjunto' => ['required', 'file', 'max:51200']],
            [
                'nuevoAdjunto.required' => 'Seleccione un archivo.',
                'nuevoAdjunto.max' => 'El archivo no puede superar los 50 MB.',
            ],
        );
        $documents->uploadForLicitacion($this->licitacion, $this->nuevoAdjunto, auth()->user());
        $this->reset('nuevoAdjunto');
        $this->licitacion->load('adjuntos.usuario');
        session()->flash('success', 'Documento cargado.');
    }

    public function render()
    {
        return view('livewire.licitaciones.procesos.show');
    }
}
