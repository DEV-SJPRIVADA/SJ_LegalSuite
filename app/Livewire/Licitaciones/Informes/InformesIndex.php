<?php

namespace App\Livewire\Licitaciones\Informes;

use App\Models\Licitaciones\Licitacion;
use App\Services\Licitaciones\LicitacionInformesService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Informes · Licitaciones')]
class InformesIndex extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'licitaciones';

    public string $fecha_desde = '';

    public string $fecha_hasta = '';

    public string $estado_proceso = 'all';

    public string $cumplimos = 'all';

    public string $licitacion_id = '';

    public string $busqueda = '';

    public function mount(): void
    {
        Gate::authorize('viewDashboard', Licitacion::class);
    }

    public function render(LicitacionInformesService $informes)
    {
        $licitacionFilters = [
            'fecha_desde' => $this->fecha_desde ?: null,
            'fecha_hasta' => $this->fecha_hasta ?: null,
            'estado_proceso' => $this->estado_proceso,
            'cumplimos' => $this->cumplimos,
        ];

        $adjuntoFilters = [
            'licitacion_id' => $this->licitacion_id !== '' ? (int) $this->licitacion_id : null,
            'fecha_desde' => $this->fecha_desde ?: null,
            'fecha_hasta' => $this->fecha_hasta ?: null,
            'busqueda' => $this->busqueda ?: null,
        ];

        return view('livewire.licitaciones.informes.index', [
            'licitaciones' => $informes->licitacionesForReport($licitacionFilters),
            'documentos' => $informes->adjuntosForReport($adjuntoFilters),
            'estadosProceso' => $informes->distinctEstadosProceso(),
            'licitacionesSelect' => Licitacion::query()
                ->orderByDesc('created_at')
                ->get(['id', 'numero_proceso', 'entidad_contratante']),
            'exportQuery' => http_build_query(array_filter([
                'fecha_desde' => $this->fecha_desde,
                'fecha_hasta' => $this->fecha_hasta,
                'estado_proceso' => $this->estado_proceso !== 'all' ? $this->estado_proceso : null,
                'cumplimos' => $this->cumplimos !== 'all' ? $this->cumplimos : null,
            ])),
        ]);
    }
}
