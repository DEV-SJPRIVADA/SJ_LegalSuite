<?php

namespace App\Livewire\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryDashboardService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard disciplinario')]
class Dashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewDashboard', DisciplinaryCase::class);
    }

    public function render()
    {
        $dashboard = app(DisciplinaryDashboardService::class);
        $actor = auth()->user();

        return view('livewire.disciplinary.dashboard', [
            'kpis' => $dashboard->kpis($actor),
            'byFault' => $dashboard->casesByFault(10, $actor),
            'byCity' => $dashboard->casesByCity($actor),
            'lawyerWorkload' => $dashboard->lawyerWorkload($actor),
        ]);
    }
}
