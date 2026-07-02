<?php

namespace App\Livewire\Licitaciones;

use App\Models\Licitaciones\Licitacion;
use App\Services\Licitaciones\LicitacionDashboardService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard · Licitaciones')]
class Dashboard extends Component
{
    public function mount(): void
    {
        if (Gate::allows('viewDashboard', Licitacion::class)) {
            return;
        }

        if (Gate::allows('viewAny', Licitacion::class)) {
            $this->redirect(route('licitaciones.procesos.index'), navigate: true);

            return;
        }

        abort(403);
    }

    public function render()
    {
        $dashboard = app(LicitacionDashboardService::class);
        $actor = auth()->user();

        return view('livewire.licitaciones.dashboard', [
            'stats' => $dashboard->stats($actor),
            'recentLicitaciones' => $dashboard->recentLicitaciones(),
            'recentSolicitudes' => $dashboard->recentSolicitudes($actor),
            'upcomingExpiries' => $dashboard->upcomingExpiries($actor),
        ]);
    }
}
