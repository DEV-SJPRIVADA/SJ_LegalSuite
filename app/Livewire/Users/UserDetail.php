<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detalle de usuario · SJ LegalSuite')]
class UserDetail extends Component
{
    public User $user;

    public bool $showPasswordModal = false;

    public string $provisionalResetPassword = '';

    public bool $passwordResetApplied = false;

    public function mount(User $user): void
    {
        Gate::authorize('view', $user);
        $this->user = $user;
    }

    public function openPasswordModal(): void
    {
        Gate::authorize('changePassword', $this->user);
        $this->provisionalResetPassword = Str::password(14, true, true, true, false);
        $this->passwordResetApplied = false;
        $this->resetErrorBag();
        $this->showPasswordModal = true;
    }

    public function closePasswordModal(): void
    {
        $this->showPasswordModal = false;
        $this->provisionalResetPassword = '';
        $this->passwordResetApplied = false;
    }

    public function confirmPasswordReset(UserService $service): void
    {
        if ($this->passwordResetApplied || $this->provisionalResetPassword === '') {
            return;
        }

        Gate::authorize('changePassword', $this->user);

        $service->resetToProvisionalPassword($this->user, $this->provisionalResetPassword);
        $this->user = $this->user->fresh();

        $this->passwordResetApplied = true;

        session()->flash(
            'success',
            "Contraseña restablecida para {$this->user->name}. Copie la contraseña provisional y compártala por un canal seguro; en el primer ingreso deberá cambiarla."
        );
    }

    public function toggleActive(UserService $service): void
    {
        Gate::authorize('toggleActive', $this->user);
        $service->toggleActive($this->user);
        $this->user = $this->user->fresh();

        session()->flash(
            'success',
            $this->user->is_active
                ? "Usuario {$this->user->name} activado."
                : "Usuario {$this->user->name} desactivado."
        );
    }

    public function render()
    {
        $this->user->load([
            'roles',
            'organizationalArea:id,name',
            'jobPosition:id,name',
            'assignedCases' => fn ($q) => $q->with('employee:id,first_name,last_name')->latest()->limit(10),
        ]);

        return view('livewire.users.show');
    }
}
