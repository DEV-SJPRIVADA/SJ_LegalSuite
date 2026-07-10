<?php

namespace App\Livewire\Users;

use App\Enums\PlatformLevel;
use App\Models\ColombianMunicipality;
use App\Models\JobPosition;
use App\Models\OrganizationalArea;
use App\Models\Role;
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

#[Layout('layouts.app')]
#[Title('Usuarios · SJ LegalSuite')]
class UsersIndex extends Component
{
    use WithPagination;

    /**
     * Claves del estado Livewire (sin puntos) => permiso Spatie.
     * Livewire interpreta los puntos en claves de array como rutas anidadas; por eso no se usan los nombres de permiso como clave.
     *
     * @var array<string, string>
     */
    private const OPERATIONS_TOGGLE_KEYS = [
        'generate_inform' => 'disciplinary.generate-inform',
        'upload_notification' => 'disciplinary.upload-notification',
        'download_pdf' => 'disciplinary.download-pdf',
    ];

    /* ---------- Filtros (sincronizados con URL) ---------- */
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url(as: 'area')]
    public string $organizationalAreaFilter = '';

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

    public ?int $organizationalAreaId = null;

    public ?int $jobPositionId = null;

    public bool $isActive = true;

    /** Si es false, el usuario queda en modo solo lectura (sin mutaciones en disciplinarios ni gestión). */
    public bool $allowChanges = true;

    /** Administrador de la plataforma (rol técnico único con todos los permisos). */
    public bool $assignPlatformAdmin = false;

    /** Permisos directos adicionales (solo UI para área Operaciones). */
    public array $directPermissionToggles = [];

    /** @var list<string> */
    public array $authorizedMunicipalityCodes = [];

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
        if (in_array($prop, ['search', 'role', 'organizationalAreaFilter', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function updatedOrganizationalAreaId(?int $value): void
    {
        $this->jobPositionId = null;
        if (! $this->shouldShowOperationsPermissionToggles()) {
            $this->resetOperationsPermissionToggles();
        }
    }

    public function updatedAssignPlatformAdmin(bool $value): void
    {
        if ($value) {
            $this->organizationalAreaId = null;
            $this->jobPositionId = null;
            $this->resetOperationsPermissionToggles();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'role', 'organizationalAreaFilter', 'status']);
        $this->resetPage();
    }

    #[Computed]
    public function organizationalAreasList()
    {
        return OrganizationalArea::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    #[Computed]
    public function jobPositionsForArea()
    {
        if (! $this->organizationalAreaId) {
            return collect();
        }

        return JobPosition::query()
            ->where('organizational_area_id', $this->organizationalAreaId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'permission_level_name']);
    }

    #[Computed]
    public function operationsPermissionLabels(): array
    {
        return [
            'generate_inform' => 'Crear informes (FO-GJ-51)',
            'upload_notification' => 'Cargar notificaciones / avisos al equipo de revisión',
            'download_pdf' => 'Descargar PDF del informe',
        ];
    }

    #[Computed]
    public function municipalitiesGrouped()
    {
        return ColombianMunicipality::groupedByDepartmentForSelect();
    }

    #[Computed]
    public function requiresAuthorizedCities(): bool
    {
        if ($this->assignPlatformAdmin) {
            return false;
        }

        $role = $this->resolvedSpatieRolesForSave()[0] ?? null;

        return in_array($role, [PlatformLevel::Nivel7->value, PlatformLevel::Nivel8->value], true);
    }

    #[Computed]
    public function rolesListForFilter()
    {
        return PlatformLevel::options();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', User::class);
        $this->resetFormState();
        $this->editingId = null;
        $this->primeOperationsPermissionDefaults();
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
        $this->assignPlatformAdmin = $user->hasRole(PlatformLevel::Nivel1->value);
        $this->organizationalAreaId = $this->assignPlatformAdmin ? null : $user->organizational_area_id;
        $this->jobPositionId = $this->assignPlatformAdmin ? null : $user->job_position_id;
        $this->authorizedMunicipalityCodes = $user->authorizedMunicipalities()
            ->pluck('municipality_code')
            ->all();

        foreach (self::OPERATIONS_TOGGLE_KEYS as $key => $perm) {
            $this->directPermissionToggles[$key] = $user->hasDirectPermission($perm);
        }

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
            'assignPlatformAdmin' => ['boolean'],
            'organizationalAreaId' => [
                Rule::requiredIf(fn () => ! $this->assignPlatformAdmin),
                'nullable',
                'integer',
                'exists:organizational_areas,id',
            ],
            'jobPositionId' => [
                Rule::requiredIf(fn () => ! $this->assignPlatformAdmin),
                'nullable',
                'integer',
                Rule::exists('job_positions', 'id')->where(function ($q) {
                    if ($this->organizationalAreaId) {
                        $q->where('organizational_area_id', $this->organizationalAreaId);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                }),
            ],
            'isActive' => ['boolean'],
            'allowChanges' => ['boolean'],
            'authorizedMunicipalityCodes' => [
                Rule::requiredIf(fn () => $this->requiresAuthorizedCities),
                'array',
                'min:1',
            ],
            'authorizedMunicipalityCodes.*' => [
                'string',
                'size:5',
                Rule::exists('colombian_municipalities', 'municipality_code'),
            ],
        ];

        $this->validate($rules);

        $rolesToSync = $this->resolvedSpatieRolesForSave();

        if (! $this->assignPlatformAdmin) {
            if ($this->jobPositionId && ! $this->organizationalAreaId) {
                $this->addError('jobPositionId', 'Seleccione un área antes de asignar un cargo.');

                return;
            }

            if ($rolesToSync === []) {
                $this->addError(
                    'jobPositionId',
                    'Este cargo no tiene perfil de permisos definido. Configúrelo en Organización o elija otro cargo.'
                );

                return;
            }
        }

        $payload = [
            'name' => $this->name,
            'email' => $this->email,
            'document_number' => $this->documentNumber ?: null,
            'phone' => $this->phone ?: null,
            'organizational_area_id' => $this->organizationalAreaId,
            'job_position_id' => $this->assignPlatformAdmin ? null : $this->jobPositionId,
            'is_active' => $this->isActive,
            'read_only' => ! $this->allowChanges,
        ];

        $directSnapshotCreate = [];
        $directSnapshotUpdate = null;
        if ($this->shouldShowOperationsPermissionToggles()) {
            $directSnapshotUpdate = [];
            foreach (self::OPERATIONS_TOGGLE_KEYS as $key => $perm) {
                $on = (bool) ($this->directPermissionToggles[$key] ?? false);
                $directSnapshotCreate[$perm] = $on;
                $directSnapshotUpdate[$perm] = $on;
            }
        }

        $municipalityCodes = $this->requiresAuthorizedCities
            ? array_values($this->authorizedMunicipalityCodes)
            : [];

        if ($this->editingId === null) {
            Gate::authorize('create', User::class);
            $result = $service->create($payload, $rolesToSync, $directSnapshotCreate, $municipalityCodes);
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
            $service->update($user, $payload, $rolesToSync, $directSnapshotUpdate, $municipalityCodes);
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

    private function primeOperationsPermissionDefaults(): void
    {
        $this->resetOperationsPermissionToggles();
    }

    private function resetOperationsPermissionToggles(): void
    {
        foreach (array_keys(self::OPERATIONS_TOGGLE_KEYS) as $key) {
            $this->directPermissionToggles[$key] = false;
        }
    }

    private function resetUserFormFields(): void
    {
        $this->reset([
            'editingId', 'name', 'email', 'documentNumber', 'phone',
            'organizationalAreaId', 'jobPositionId', 'authorizedMunicipalityCodes',
        ]);
        $this->assignPlatformAdmin = false;
        $this->isActive = true;
        $this->allowChanges = true;
        $this->primeOperationsPermissionDefaults();
        $this->resetErrorBag();
    }

    private function resetFormState(): void
    {
        $this->resetUserFormFields();
        $this->closeCredentialModal();
    }

    private function shouldShowOperationsPermissionToggles(): bool
    {
        if ($this->assignPlatformAdmin) {
            return false;
        }

        if (! $this->organizationalAreaId) {
            return false;
        }

        $slug = OrganizationalArea::whereKey($this->organizationalAreaId)->value('slug');

        return $slug === 'operaciones';
    }

    /* ---------- Toggle activo / Eliminar ---------- */

    /**
     * @return list<string>
     */
    private function resolvedSpatieRolesForSave(): array
    {
        if ($this->assignPlatformAdmin) {
            return [PlatformLevel::Nivel1->value];
        }

        if (! $this->jobPositionId) {
            return [];
        }

        $job = JobPosition::find($this->jobPositionId);
        $roleName = $job?->permission_level_name;

        if (! $roleName || ! Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
            return [];
        }

        return [$roleName];
    }

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
            ->with(['roles:id,name', 'organizationalArea:id,name', 'jobPosition:id,name,permission_level_name'])
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
            ->when($this->organizationalAreaFilter !== '', fn ($q) => $q->where('organizational_area_id', (int) $this->organizationalAreaFilter))
            ->when($this->status === 'activos', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactivos', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.users.index', [
            'users' => $users,
            'showOperationsToggles' => $this->shouldShowOperationsPermissionToggles(),
            'operationsPermissionLabels' => $this->operationsPermissionLabels,
        ]);
    }
}
