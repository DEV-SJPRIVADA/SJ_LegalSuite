<?php

namespace App\Livewire\Licitaciones\Solicitudes;

use App\Enums\Licitaciones\Periodicity;
use App\Enums\Licitaciones\PetitionType;
use App\Enums\Licitaciones\RequestType;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;
use App\Services\Licitaciones\LicitacionSolicitudService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Solicitudes · Licitaciones')]
class SolicitudesIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $licitacion_id = null;

    public string $numero_radicado = '';

    public string $nombre = '';

    public string $descripcion = '';

    public string $area_responsable = '';

    public ?int $usuario_responsable_id = null;

    public string $tipo_solicitud = 'esporadica';

    public string $periodicidad = '';

    public string $tipo_peticion = 'informacion';

    public ?string $fecha_limite = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', LicitacionSolicitud::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', LicitacionSolicitud::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(LicitacionSolicitudService $service): void
    {
        Gate::authorize('create', LicitacionSolicitud::class);

        $data = $this->validate([
            'licitacion_id' => [
                Rule::requiredIf($this->tipo_solicitud === RequestType::Esporadica->value),
                'nullable',
                'exists:licitaciones,id',
            ],
            'numero_radicado' => ['required', 'string', 'max:100', Rule::unique('licitacion_solicitudes', 'numero_radicado')],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'area_responsable' => ['required', 'string', 'max:255'],
            'usuario_responsable_id' => ['required', 'exists:users,id'],
            'tipo_solicitud' => ['required', Rule::enum(RequestType::class)],
            'periodicidad' => ['nullable', Rule::enum(Periodicity::class)],
            'tipo_peticion' => ['required', Rule::enum(PetitionType::class)],
            'fecha_limite' => ['required', 'date'],
        ]);

        $data['periodicidad'] = $data['periodicidad'] ?: null;
        $data['licitacion_id'] = $data['licitacion_id'] ?: null;

        try {
            $service->create($data, auth()->user());
            session()->flash('success', 'Solicitud creada.');
            $this->closeForm();
        } catch (\InvalidArgumentException $e) {
            $this->addError('licitacion_id', $e->getMessage());
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        $solicitudes = LicitacionSolicitud::query()
            ->forActor(auth()->user())
            ->search($this->search)
            ->with(['licitacion:id,numero_proceso,entidad_contratante', 'usuarioResponsable:id,name'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $licitaciones = Licitacion::orderByDesc('created_at')->get(['id', 'numero_proceso', 'entidad_contratante']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.licitaciones.solicitudes.index', compact('solicitudes', 'licitaciones', 'usuarios'));
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'licitacion_id', 'numero_radicado', 'nombre', 'descripcion',
            'area_responsable', 'usuario_responsable_id', 'tipo_solicitud', 'periodicidad',
            'tipo_peticion', 'fecha_limite',
        ]);
        $this->tipo_solicitud = RequestType::Esporadica->value;
        $this->tipo_peticion = PetitionType::Informacion->value;
    }
}
