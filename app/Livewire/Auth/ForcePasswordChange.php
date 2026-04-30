<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Nueva contraseña')]
class ForcePasswordChange extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! Auth::user()->must_change_password) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function save(): void
    {
        $this->validate([
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        Auth::user()->forceFill([
            'password' => $this->password,
            'must_change_password' => false,
        ])->save();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
