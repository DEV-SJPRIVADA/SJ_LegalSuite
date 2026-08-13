<?php

namespace App\Livewire\Users;

use App\Enums\EmployeeScope;
use App\Enums\PlatformLevel;
use App\Models\EmployeeJobPosition;
use App\Models\JobPosition;
use App\Models\OrganizationalArea;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Organización · SJ LegalSuite')]
class OrganizationCatalog extends Component
{
    public ?int $selectedAreaId = null;

    /* Área */
    public string $areaName = '';

    public string $areaSlug = '';

    public int $areaSortOrder = 0;

    public bool $areaIsActive = true;

    public ?int $editingAreaId = null;

    /* Cargo */
    public string $positionName = '';

    public string $positionPermissionRole = '';

    public int $positionSortOrder = 0;

    public bool $positionIsActive = true;

    public ?int $editingPositionId = null;

    /* Cargo de empleado (RRHH) */
    public string $employeePositionName = '';

    public string $employeePositionSlug = '';

    public bool $employeePositionIsGuarda = false;

    public string $employeePositionScope = '';

    public int $employeePositionSortOrder = 0;

    public bool $employeePositionIsActive = true;

    public ?int $editingEmployeePositionId = null;

    public function mount(): void
    {
        Gate::authorize('create', User::class);

        $first = OrganizationalArea::query()->orderBy('sort_order')->orderBy('name')->first();
        $this->selectedAreaId = $first?->id;
        $this->editingAreaId = null;
        $this->areaName = '';
        $this->areaSlug = '';
        $this->areaSortOrder = (int) OrganizationalArea::max('sort_order') + 10;
        $this->areaIsActive = true;

        $this->resetPositionForm();
        $this->resetEmployeePositionForm();
    }

    public function updatedSelectedAreaId(): void
    {
        $this->resetPositionForm();
    }

    public function updatedAreaName(string $value): void
    {
        if ($this->editingAreaId === null && $this->areaSlug === '') {
            $this->areaSlug = OrganizationalArea::slugFromLabel($value);
        }
    }

    #[Computed]
    public function permissionRoleNameOptions(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('name', '!=', PlatformLevel::Nivel1->value)
            ->orderBy('level_number')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->name => $role->displayTitle().' — '.($role->displaySubtitle() ?? ''),
            ])
            ->all();
    }

    public function startCreateArea(): void
    {
        $this->editingAreaId = null;
        $this->areaName = '';
        $this->areaSlug = '';
        $this->areaSortOrder = (int) OrganizationalArea::max('sort_order') + 10;
        $this->areaIsActive = true;
        $this->resetErrorBag();
    }

    public function editArea(int $id): void
    {
        $a = OrganizationalArea::findOrFail($id);
        $this->editingAreaId = $a->id;
        $this->areaName = $a->name;
        $this->areaSlug = $a->slug;
        $this->areaSortOrder = $a->sort_order;
        $this->areaIsActive = $a->is_active;
        $this->selectedAreaId = $a->id;
        $this->resetPositionForm();
    }

    public function saveArea(): void
    {
        Gate::authorize('create', User::class);

        $slugRule = Rule::unique('organizational_areas', 'slug')->ignore($this->editingAreaId);

        $this->validate([
            'areaName' => ['required', 'string', 'max:120'],
            'areaSlug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'areaSortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
            'areaIsActive' => ['boolean'],
        ], [], [
            'areaName' => 'nombre',
            'areaSlug' => 'slug',
        ]);

        if ($this->editingAreaId) {
            $a = OrganizationalArea::findOrFail($this->editingAreaId);
            $a->update([
                'name' => $this->areaName,
                'slug' => $this->areaSlug,
                'sort_order' => $this->areaSortOrder,
                'is_active' => $this->areaIsActive,
            ]);
            session()->flash('success', 'Área actualizada.');
        } else {
            $a = OrganizationalArea::create([
                'name' => $this->areaName,
                'slug' => $this->areaSlug,
                'sort_order' => $this->areaSortOrder,
                'is_active' => $this->areaIsActive,
            ]);
            $this->selectedAreaId = $a->id;
            $this->editingAreaId = $a->id;
            session()->flash('success', 'Área creada.');
        }
    }

    public function deleteArea(int $id): void
    {
        Gate::authorize('create', User::class);

        if (User::query()->where('organizational_area_id', $id)->exists()) {
            session()->flash('error', 'Hay usuarios asignados a esta área; reasígnelos antes de eliminar.');

            return;
        }

        $a = OrganizationalArea::findOrFail($id);

        if ($a->jobPositions()->exists()) {
            session()->flash('error', 'No se puede eliminar el área con cargos definidos. Elimine o desactive los cargos primero.');

            return;
        }

        $a->delete();

        if ($this->selectedAreaId === $id) {
            $this->selectedAreaId = OrganizationalArea::query()->orderBy('sort_order')->orderBy('name')->value('id');
        }

        $this->startCreateArea();
        $this->resetPositionForm();

        session()->flash('success', 'Área eliminada.');
    }

    public function startCreatePosition(): void
    {
        $this->editingPositionId = null;
        $this->positionName = '';
        $this->positionPermissionRole = '';
        $this->positionSortOrder = (int) JobPosition::where('organizational_area_id', $this->selectedAreaId)->max('sort_order') + 10;
        $this->positionIsActive = true;
    }

    public function editPosition(int $id): void
    {
        $p = JobPosition::findOrFail($id);
        $this->editingPositionId = $p->id;
        $this->positionName = $p->name;
        $this->positionPermissionRole = (string) ($p->permission_level_name ?? '');
        $this->positionSortOrder = $p->sort_order;
        $this->positionIsActive = $p->is_active;
    }

    public function savePosition(): void
    {
        Gate::authorize('create', User::class);

        $this->validate([
            'positionName' => ['required', 'string', 'max:160'],
            'positionPermissionRole' => ['required', 'string', 'max:64', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'positionSortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
            'positionIsActive' => ['boolean'],
            'selectedAreaId' => ['required', 'exists:organizational_areas,id'],
        ], [], [
            'positionName' => 'cargo',
            'positionPermissionRole' => 'perfil de permisos',
        ]);

        $payload = [
            'organizational_area_id' => $this->selectedAreaId,
            'name' => $this->positionName,
            'permission_level_name' => $this->positionPermissionRole,
            'sort_order' => $this->positionSortOrder,
            'is_active' => $this->positionIsActive,
        ];

        if ($this->editingPositionId) {
            JobPosition::whereKey($this->editingPositionId)->update($payload);
            session()->flash('success', 'Cargo actualizado.');
        } else {
            JobPosition::create($payload);
            session()->flash('success', 'Cargo creado.');
        }

        $this->resetPositionForm();
    }

    public function deletePosition(int $id): void
    {
        Gate::authorize('create', User::class);

        $p = JobPosition::findOrFail($id);

        if ($p->users()->exists()) {
            session()->flash('error', 'Hay usuarios asignados a este cargo; reasígnelos antes de borrar.');

            return;
        }

        $p->delete();

        session()->flash('success', 'Cargo eliminado.');
    }

    public function updatedEmployeePositionName(string $value): void
    {
        if ($this->editingEmployeePositionId === null && $this->employeePositionSlug === '') {
            $this->employeePositionSlug = EmployeeJobPosition::slugFromLabel($value);
        }
    }

    public function startCreateEmployeePosition(): void
    {
        $this->resetEmployeePositionForm();
        $this->resetErrorBag();
    }

    public function editEmployeePosition(int $id): void
    {
        $position = EmployeeJobPosition::findOrFail($id);
        $this->editingEmployeePositionId = $position->id;
        $this->employeePositionName = $position->name;
        $this->employeePositionSlug = $position->slug;
        $this->employeePositionIsGuarda = (bool) $position->is_guarda;
        $this->employeePositionScope = (string) ($position->employee_scope?->value ?? EmployeeScope::Administrativo->value);
        $this->employeePositionSortOrder = $position->sort_order;
        $this->employeePositionIsActive = $position->is_active;
        $this->resetErrorBag();
    }

    public function saveEmployeePosition(): void
    {
        Gate::authorize('create', User::class);

        $slugRule = Rule::unique('employee_job_positions', 'slug')->ignore($this->editingEmployeePositionId);

        $this->validate([
            'employeePositionName' => ['required', 'string', 'max:120'],
            'employeePositionSlug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'employeePositionIsGuarda' => ['boolean'],
            'employeePositionScope' => ['required', Rule::enum(EmployeeScope::class)],
            'employeePositionSortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
            'employeePositionIsActive' => ['boolean'],
        ], [], [
            'employeePositionName' => 'nombre',
            'employeePositionSlug' => 'slug',
        ]);

        $payload = [
            'name' => $this->employeePositionName,
            'slug' => $this->employeePositionSlug,
            'is_guarda' => $this->employeePositionIsGuarda,
            'employee_scope' => $this->employeePositionScope,
            'sort_order' => $this->employeePositionSortOrder,
            'is_active' => $this->employeePositionIsActive,
        ];

        if ($this->editingEmployeePositionId) {
            EmployeeJobPosition::whereKey($this->editingEmployeePositionId)->update($payload);
            session()->flash('success', 'Cargo de empleado actualizado.');
        } else {
            EmployeeJobPosition::create($payload);
            session()->flash('success', 'Cargo de empleado creado.');
        }

        $this->resetEmployeePositionForm();
    }

    public function deleteEmployeePosition(int $id): void
    {
        Gate::authorize('create', User::class);

        $position = EmployeeJobPosition::findOrFail($id);

        if ($position->employees()->exists()) {
            session()->flash('error', 'Hay empleados con este cargo; reasígnelos antes de eliminar.');

            return;
        }

        $position->delete();

        if ($this->editingEmployeePositionId === $id) {
            $this->resetEmployeePositionForm();
        }

        session()->flash('success', 'Cargo de empleado eliminado.');
    }

    private function resetEmployeePositionForm(): void
    {
        $this->editingEmployeePositionId = null;
        $this->employeePositionName = '';
        $this->employeePositionSlug = '';
        $this->employeePositionIsGuarda = false;
        $this->employeePositionScope = EmployeeScope::Administrativo->value;
        $this->employeePositionSortOrder = (int) EmployeeJobPosition::max('sort_order') + 10;
        $this->employeePositionIsActive = true;
    }

    private function resetPositionForm(): void
    {
        $this->editingPositionId = null;
        $this->positionName = '';
        $this->positionPermissionRole = '';
        $this->positionSortOrder = (int) JobPosition::where('organizational_area_id', $this->selectedAreaId)->max('sort_order') + 10;
        $this->positionIsActive = true;
    }

    public function render()
    {
        $areas = OrganizationalArea::query()->orderBy('sort_order')->orderBy('name')->get();

        $positions = collect();

        if ($this->selectedAreaId) {
            $positions = JobPosition::query()
                ->where('organizational_area_id', $this->selectedAreaId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return view('livewire.users.organization-catalog', [
            'areas' => $areas,
            'positions' => $positions,
            'employeePositions' => EmployeeJobPosition::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
