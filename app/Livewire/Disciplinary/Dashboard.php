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
        if (Gate::allows('viewDashboard', DisciplinaryCase::class)) {
            return;
        }

        if (Gate::allows('viewAny', DisciplinaryCase::class)) {
            $this->redirect(route('disciplinary.cases.index'), navigate: true);

            return;
        }

        abort(403);
    }

    public function render()
    {
        $dashboard = app(DisciplinaryDashboardService::class);
        $actor = auth()->user();

        return view('livewire.disciplinary.dashboard', [
            'workflowDonuts' => $dashboard->workflowStageDonuts($actor),
            'byFault' => $dashboard->casesByFault(10, $actor),
            'byCity' => $dashboard->casesByCity($actor),
            'lawyerWorkload' => $dashboard->lawyerWorkload($actor),
        ]);
    }
}
