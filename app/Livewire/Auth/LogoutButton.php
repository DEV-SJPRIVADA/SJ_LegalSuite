<?php

namespace App\Livewire\Auth;

use App\Livewire\Actions\Logout;
use Livewire\Component;

class LogoutButton extends Component
{
    /** @var string light|dark */
    public string $variant = 'light';

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}
