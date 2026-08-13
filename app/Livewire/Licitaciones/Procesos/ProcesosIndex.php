<?php

namespace App\Livewire\Licitaciones\Procesos;

use App\Enums\PlatformLevel;
use App\Models\Licitaciones\Licitacion;
use App\Models\User;
use App\Services\Licitaciones\LicitacionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Licitaciones')]
class ProcesosIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $entidad_contratante = '';

    public string $modalidad_contratacion = '';

    public string $numero_proceso = '';

    public string $objeto = '';

    public string $cuantia = '';

    public string $plazo_ejecucion = '';

    public string $lugar_ejecucion = '';

    public string $medio_presentacion = '';

    public string $enlace_proceso = '';

    public string $participacion_tipo = '';

    public string $integrantes_participacion = '';

    public ?string $fecha_cierre_oferta = null;

    public string $hora_cierre_oferta = '';

    public ?string $fecha_observaciones_evaluacion = null;

    public ?string $fecha_adjudicacion = null;

    public string $cumplimos = '';

    public string $motivo_no_cumplir = '';

    public string $estado_proceso = '';

    public string $resultado = '';

    public string $adjudicado = '';

    public string $motivo_perdida = '';

    public ?int $responsable_principal_id = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Licitacion::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Licitacion::class);
        $this->resetForm();
        $this->editingId = null;
        $this->responsable_principal_id = auth()->id();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $licitacion = Licitacion::findOrFail($id);
        Gate::authorize('update', $licitacion);

        $this->editingId = $licitacion->id;
        $this->entidad_contratante = (string) ($licitacion->entidad_contratante ?? '');
        $this->modalidad_contratacion = (string) ($licitacion->modalidad_contratacion ?? '');
        $this->numero_proceso = (string) ($licitacion->numero_proceso ?? '');
        $this->objeto = (string) ($licitacion->objeto ?? '');
        $this->cuantia = (string) ($licitacion->cuantia ?? '');
        $this->plazo_ejecucion = (string) ($licitacion->plazo_ejecucion ?? '');
        $this->lugar_ejecucion = (string) ($licitacion->lugar_ejecucion ?? '');
        $this->medio_presentacion = (string) ($licitacion->medio_presentacion ?? '');
        $this->enlace_proceso = (string) ($licitacion->enlace_proceso ?? '');
        $this->participacion_tipo = (string) ($licitacion->participacion_tipo ?? '');
        $this->integrantes_participacion = (string) ($licitacion->integrantes_participacion ?? '');
        $this->fecha_cierre_oferta = $licitacion->fecha_cierre_oferta?->format('Y-m-d');
        $this->hora_cierre_oferta = (string) ($licitacion->hora_cierre_oferta ?? '');
        $this->fecha_observaciones_evaluacion = $licitacion->fecha_observaciones_evaluacion?->format('Y-m-d');
        $this->fecha_adjudicacion = $licitacion->fecha_adjudicacion?->format('Y-m-d');
        $this->cumplimos = (string) ($licitacion->cumplimos ?? '');
        $this->motivo_no_cumplir = (string) ($licitacion->motivo_no_cumplir ?? '');
        $this->estado_proceso = (string) ($licitacion->estado_proceso ?? '');
        $this->resultado = (string) ($licitacion->resultado ?? '');
        $this->adjudicado = (string) ($licitacion->adjudicado ?? '');
        $this->motivo_perdida = (string) ($licitacion->motivo_perdida ?? '');
        $this->responsable_principal_id = $licitacion->responsable_principal_id;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(LicitacionService $service): void
    {
        $data = $this->validate($this->rules()) + $this->formPayload();

        if ($this->editingId) {
            $licitacion = Licitacion::findOrFail($this->editingId);
            Gate::authorize('update', $licitacion);
            $service->update($licitacion, $data);
            session()->flash('success', 'Licitación actualizada.');
        } else {
            Gate::authorize('create', Licitacion::class);
            $service->create($data, auth()->user());
            session()->flash('success', 'Licitación creada.');
        }

        $this->closeForm();
    }

    public function delete(int $id, LicitacionService $service): void
    {
        $licitacion = Licitacion::findOrFail($id);
        Gate::authorize('delete', $licitacion);

        try {
            $service->delete($licitacion);
            session()->flash('success', 'Licitación eliminada.');
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $licitaciones = Licitacion::query()
            ->search($this->search)
            ->with('responsablePrincipal:id,name')
            ->orderByDesc('created_at')
            ->paginate(15);

        $abogados = User::queryByPlatformLevels(PlatformLevel::Nivel1, PlatformLevel::Nivel6)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.licitaciones.procesos.index', compact('licitaciones', 'abogados'));
    }

    private function rules(): array
    {
        return [
            'responsable_principal_id' => ['required', 'exists:users,id'],
            'entidad_contratante' => ['nullable', 'string', 'max:255'],
            'modalidad_contratacion' => ['nullable', 'string', 'max:255'],
            'numero_proceso' => ['nullable', 'string', 'max:255'],
            'objeto' => ['nullable', 'string'],
            'fecha_cierre_oferta' => ['nullable', 'date'],
            'hora_cierre_oferta' => ['nullable', 'string', 'max:8'],
            'fecha_observaciones_evaluacion' => ['nullable', 'date'],
            'fecha_adjudicacion' => ['nullable', 'date'],
            'enlace_proceso' => ['nullable', 'url', 'max:2048'],
            'cuantia' => ['nullable', 'string', 'max:255'],
            'plazo_ejecucion' => ['nullable', 'string', 'max:255'],
            'lugar_ejecucion' => ['nullable', 'string', 'max:255'],
            'medio_presentacion' => ['nullable', 'string', 'max:255'],
            'participacion_tipo' => ['nullable', 'in:IND,UT'],
            'integrantes_participacion' => ['nullable', 'string'],
            'cumplimos' => ['nullable', 'in:SI,NO'],
            'motivo_no_cumplir' => ['nullable', 'string'],
            'estado_proceso' => ['nullable', 'string', 'max:255'],
            'resultado' => ['nullable', 'string', 'max:255'],
            'adjudicado' => ['nullable', 'in:Si,No'],
            'motivo_perdida' => ['required_if:adjudicado,No', 'nullable', 'string'],
        ];
    }

  /**
     * @return array<string, mixed>
     */
    private function formPayload(): array
    {
        return [
            'responsable_principal_id' => $this->responsable_principal_id,
            'entidad_contratante' => $this->entidad_contratante ?: null,
            'modalidad_contratacion' => $this->modalidad_contratacion ?: null,
            'numero_proceso' => $this->numero_proceso ?: null,
            'objeto' => $this->objeto ?: null,
            'cuantia' => $this->cuantia ?: null,
            'plazo_ejecucion' => $this->plazo_ejecucion ?: null,
            'lugar_ejecucion' => $this->lugar_ejecucion ?: null,
            'medio_presentacion' => $this->medio_presentacion ?: null,
            'enlace_proceso' => $this->enlace_proceso ?: null,
            'participacion_tipo' => $this->participacion_tipo ?: null,
            'integrantes_participacion' => $this->integrantes_participacion ?: null,
            'fecha_cierre_oferta' => $this->fecha_cierre_oferta,
            'hora_cierre_oferta' => $this->hora_cierre_oferta ?: null,
            'fecha_observaciones_evaluacion' => $this->fecha_observaciones_evaluacion,
            'fecha_adjudicacion' => $this->fecha_adjudicacion,
            'cumplimos' => $this->cumplimos ?: null,
            'motivo_no_cumplir' => $this->motivo_no_cumplir ?: null,
            'estado_proceso' => $this->estado_proceso ?: null,
            'resultado' => $this->resultado ?: null,
            'adjudicado' => $this->adjudicado ?: null,
            'motivo_perdida' => $this->adjudicado === 'No' ? ($this->motivo_perdida ?: null) : null,
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'entidad_contratante', 'modalidad_contratacion', 'numero_proceso', 'objeto',
            'cuantia', 'plazo_ejecucion', 'lugar_ejecucion', 'medio_presentacion', 'enlace_proceso',
            'participacion_tipo', 'integrantes_participacion', 'fecha_cierre_oferta', 'hora_cierre_oferta',
            'fecha_observaciones_evaluacion', 'fecha_adjudicacion', 'cumplimos', 'motivo_no_cumplir',
            'estado_proceso', 'resultado', 'adjudicado', 'motivo_perdida', 'responsable_principal_id',
        ]);
    }
}
