<?php

namespace App\Livewire\Users;

use App\Enums\UserArea;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Usuarios · SJ LegalSuite')]
class UsersIndex extends Component
{
    use WithPagination;

    /* ---------- Filtros (sincronizados con URL) ---------- */
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $area = '';

    #[Url(as: 'estado')]
    public string $status = '';

    public int $perPage = 15;

    /* ---------- Modal de creación/edición ---------- */
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $documentNumber = '';

    public string $phone = '';

    public string $area_value = '';

    public string $position = '';

    public bool $isActive = true;

    /** Si es false, el usuario queda en modo solo lectura (sin mutaciones en disciplinarios ni gestión). */
    public bool $allowChanges = true;

    public array $userRoles = [];

    /** Contraseña provisional mostrada solo tras crear usuario */
    public bool $showCredentialModal = false;

    public string $generatedPlainPassword = '';

    /* ---------- Modal de password ---------- */
    public bool $showPasswordModal = false;

    public ?int $passwordTargetId = null;

    /** Contraseña generada para reinicio administrativo (visible hasta cerrar el modal). */
    public string $provisionalResetPassword = '';

    public bool $passwordResetApplied = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['search', 'role', 'area', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'role', 'area', 'status']);
        $this->resetPage();
    }

    /* ---------- Datos auxiliares ---------- */

    #[Computed]
    public function rolesList()
    {
        return Role::orderBy('name')->pluck('name')->all();
    }

    #[Computed]
    public function areasList(): array
    {
        return UserArea::options();
    }

    /* ---------- Crear / Editar ---------- */

    public function openCreate(): void
    {
        Gate::authorize('create', User::class);
        $this->resetFormState();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->documentNumber = (string) ($user->document_number ?? '');
        $this->phone = (string) ($user->phone ?? '');
        $this->area_value = $user->area?->value ?? '';
        $this->position = (string) ($user->position ?? '');
        $this->isActive = (bool) $user->is_active;
        $this->allowChanges = ! $user->read_only;
        $this->userRoles = $user->roles->pluck('name')->all();

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetFormState();
    }

    public function save(UserService $service): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'documentNumber' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'area_value' => ['nullable', Rule::in(array_keys(UserArea::options()))],
            'position' => ['nullable', 'string', 'max:120'],
            'isActive' => ['boolean'],
            'allowChanges' => ['boolean'],
            'userRoles' => ['array'],
            'userRoles.*' => ['string', Rule::exists('roles', 'name')],
        ];

        $this->validate($rules);

        $payload = [
            'name' => $this->name,
            'email' => $this->email,
            'document_number' => $this->documentNumber ?: null,
            'phone' => $this->phone ?: null,
            'area' => $this->area_value ?: null,
            'position' => $this->position ?: null,
            'is_active' => $this->isActive,
            'read_only' => ! $this->allowChanges,
        ];

        if ($this->editingId === null) {
            Gate::authorize('create', User::class);
            $result = $service->create($payload, $this->userRoles);
            $this->showForm = false;
            $this->resetUserFormFields();
            $this->generatedPlainPassword = $result['plain_password'];
            $this->showCredentialModal = true;
            session()->flash(
                'success',
                'Usuario creado. Copie la contraseña provisional y compártala por un canal seguro. En el primer ingreso deberá cambiarla.'
            );
        } else {
            $user = User::findOrFail($this->editingId);
            Gate::authorize('update', $user);
            $service->update($user, $payload, $this->userRoles);
            session()->flash('success', 'Usuario actualizado correctamente.');
            $this->showForm = false;
            $this->resetFormState();
        }
    }

    public function closeCredentialModal(): void
    {
        $this->showCredentialModal = false;
        $this->generatedPlainPassword = '';
    }

    private function resetUserFormFields(): void
    {
        $this->reset([
            'editingId', 'name', 'email', 'documentNumber', 'phone',
            'area_value', 'position', 'userRoles',
        ]);
        $this->isActive = true;
        $this->allowChanges = true;
        $this->resetErrorBag();
    }

    private function resetFormState(): void
    {
        $this->resetUserFormFields();
        $this->closeCredentialModal();
    }

    /* ---------- Toggle activo / Eliminar ---------- */

    public function toggleActive(int $id, UserService $service): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('toggleActive', $user);
        $service->toggleActive($user);

        $user->refresh();

        session()->flash(
            'success',
            $user->is_active
                ? "Usuario {$user->name} activado."
                : "Usuario {$user->name} desactivado."
        );
    }

    public function deleteUser(int $id, UserService $service): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('delete', $user);
        $service->delete($user);

        session()->flash('success', "Usuario {$user->name} eliminado.");
    }

    /* ---------- Cambiar password ---------- */

    public function openPasswordModal(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('changePassword', $user);
        $this->passwordTargetId = $id;
        $this->provisionalResetPassword = Str::password(14, true, true, true, false);
        $this->passwordResetApplied = false;
        $this->resetErrorBag();
        $this->showPasswordModal = true;
    }

    public function closePasswordModal(): void
    {
        $this->showPasswordModal = false;
        $this->passwordTargetId = null;
        $this->provisionalResetPassword = '';
        $this->passwordResetApplied = false;
    }

    public function confirmPasswordReset(UserService $service): void
    {
        if ($this->passwordResetApplied || $this->passwordTargetId === null || $this->provisionalResetPassword === '') {
            return;
        }

        $user = User::findOrFail($this->passwordTargetId);
        Gate::authorize('changePassword', $user);

        $service->resetToProvisionalPassword($user, $this->provisionalResetPassword);

        $this->passwordResetApplied = true;

        session()->flash(
            'success',
            "Contraseña restablecida para {$user->name}. Copie la contraseña provisional y compártala por un canal seguro; en el primer ingreso deberá cambiarla."
        );
    }

    public function render()
    {
        $users = User::query()
            ->with('roles:id,name')
            ->withCount(['assignedCases', 'reportedCases'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.str_replace(' ', '%', $this->search).'%';
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('document_number', 'like', $term);
                });
            })
            ->when($this->role !== '', fn ($q) => $q->role($this->role))
            ->when($this->area !== '', fn ($q) => $q->where('area', $this->area))
            ->when($this->status === 'activos', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactivos', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.users.index', [
            'users' => $users,
        ]);
    }
}
