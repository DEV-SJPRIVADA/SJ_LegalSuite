<?php

namespace App\Livewire\Settings;

use App\Models\Disciplinary\SupervisionZone;
use App\Services\Disciplinary\SupervisionZoneService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ajustes · Zonas de supervisión · SJ LegalSuite')]
class SupervisionZonesIndex extends Component
{
    public string $name = '';

    public string $code = '';

    public string $notificationEmail = '';

    public bool $isActive = true;

    public int $sortOrder = 0;

    public ?int $editingId = null;

    public bool $showForm = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-supervision-zones') ?? false, 403);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-supervision-zones') ?? false, 403);
        $this->resetForm();
        $this->sortOrder = (int) SupervisionZone::query()->max('sort_order') + 10;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('settings.manage-supervision-zones') ?? false, 403);
        $zone = SupervisionZone::query()->findOrFail($id);
        $this->editingId = $zone->id;
        $this->name = $zone->name;
        $this->code = (string) ($zone->code ?? '');
        $this->notificationEmail = (string) ($zone->notification_email ?? '');
        $this->isActive = (bool) $zone->is_active;
        $this->sortOrder = (int) $zone->sort_order;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(SupervisionZoneService $zones): void
    {
        abort_unless(auth()->user()?->can('settings.manage-supervision-zones') ?? false, 403);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('supervision_zones', 'code')->ignore($this->editingId),
            ],
            'notificationEmail' => ['nullable', 'email', 'max:190'],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
        ], [], [
            'name' => 'nombre',
            'code' => 'código',
            'notificationEmail' => 'correo de notificación',
        ]);

        $payload = [
            'name' => $this->name,
            'code' => $this->code,
            'notification_email' => $this->notificationEmail,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId === null) {
            $zones->create($payload);
            session()->flash('success', 'Zona de supervisión creada.');
        } else {
            $zone = SupervisionZone::query()->findOrFail($this->editingId);
            $zones->update($zone, $payload);
            session()->flash('success', 'Zona de supervisión actualizada.');
        }

        $this->closeForm();
    }

    public function deleteZone(int $id, SupervisionZoneService $zones): void
    {
        abort_unless(auth()->user()?->can('settings.manage-supervision-zones') ?? false, 403);

        try {
            $zone = SupervisionZone::query()->findOrFail($id);
            $zones->delete($zone);
            session()->flash('success', 'Zona de supervisión eliminada.');
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'No se pudo eliminar la zona.';
            session()->flash('error', $msg);
        }
    }

    public function render(SupervisionZoneService $zones)
    {
        return view('livewire.settings.supervision-zones-index', [
            'zones' => $zones->allZonesOrdered()->load(['users:id,name,email']),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'notificationEmail']);
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->resetErrorBag();
    }
}
