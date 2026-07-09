<?php

namespace App\Livewire;

use App\Services\HomeDashboardService;
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

        if ($user->canViewHomeCommandCenter()) {
            return;
        }

        $this->redirect($user->suiteLandingUrl(), navigate: true);
    }

    public function render(HomeDashboardService $dashboard)
    {
        $user = auth()->user();
        abort_unless($user->canViewHomeCommandCenter(), 403);

        $data = $dashboard->build($user);

        return view('livewire.home', [
            'dashboard' => $data,
            'summary' => $data['summary'],
            'trend' => $data['trend'],
            'kpis' => $data['kpis'],
            'workflow' => $data['workflow'],
            'lawyerWorkload' => $data['lawyerWorkload'],
            'caseMapPins' => $data['caseMapPins'],
            'topMunicipalities' => $data['topMunicipalities'],
            'casesWithoutMunicipalityCount' => $data['casesWithoutMunicipalityCount'],
        ]);
    }
}
