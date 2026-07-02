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
        // Recarga completa: tras invalidate() el token CSRF del DOM queda inválido y wire:navigate devuelve 419.
        $this->redirect('/', navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}
