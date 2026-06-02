<?php

namespace App\Livewire;

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

        if ($user->hasDisciplinaryPortalAccess()) {
            $this->redirect($user->disciplinaryPortalUrl(), navigate: true);
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
