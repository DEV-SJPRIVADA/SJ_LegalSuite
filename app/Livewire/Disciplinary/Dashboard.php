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

        return view('livewire.disciplinary.dashboard', [
            'kpis' => $dashboard->kpis(),
            'byFault' => $dashboard->casesByFault(),
            'byCity' => $dashboard->casesByCity(),
            'lawyerWorkload' => $dashboard->lawyerWorkload(),
        ]);
    }
}
