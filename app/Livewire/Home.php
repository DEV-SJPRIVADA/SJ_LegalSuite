<?php

namespace App\Livewire;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\AlertsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Inicio · SJ LegalSuite')]
class Home extends Component
{
    public function mount(): void
    {
        $user = auth()->user();

        if ($user->canSeeFullAppSidebar()) {
            return;
        }

        $model = DisciplinaryCase::class;

        if ($user->can('viewDashboard', $model)) {
            $this->redirect(route('disciplinary.dashboard'), navigate: true);

            return;
        }

        if ($user->can('viewAny', $model)) {
            $this->redirect(route('disciplinary.cases.index'), navigate: true);
        }
    }

    public function render()
    {
        $alerts = app(AlertsService::class);
        $user = auth()->user();

        return view('livewire.home', [
            'summary' => $alerts->summary(5, $user),
            'trend' => $alerts->monthlyTrend(6, $user),
        ]);
    }
}
