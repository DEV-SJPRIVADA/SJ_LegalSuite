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
    public function render()
    {
        $alerts = app(AlertsService::class);

        return view('livewire.home', [
            'summary' => $alerts->summary(),
            'trend' => $alerts->monthlyTrend(),
        ]);
    }
}
