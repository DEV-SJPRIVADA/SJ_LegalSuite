<?php

namespace App\Livewire\Settings;

use App\Models\ColombianMunicipality;
use App\Services\Settings\ColombianMunicipalityImportService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Ajustes · Territorio · SJ LegalSuite')]
class TerritoryImport extends Component
{
    use WithFileUploads, WithPagination;

    /** @var mixed */
    public $file = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'depto')]
    public string $departmentFilter = '';

    public int $perPage = 50;

    /** @var array{inserted: int, updated: int}|null */
    public ?array $lastImportResult = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-territory') ?? false, 403);
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['search', 'departmentFilter', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function updatedPerPage(mixed $value): void
    {
        $allowed = [50, 100];
        $this->perPage = in_array((int) $value, $allowed, true) ? (int) $value : 50;
    }

    public function clearFile(): void
    {
        $this->reset('file');
        $this->resetErrorBag();
    }

    public function clearExplorerFilters(): void
    {
        $this->reset(['search', 'departmentFilter']);
        $this->resetPage();
    }

    public function dismissImportResult(): void
    {
        $this->lastImportResult = null;
    }

    public function import(ColombianMunicipalityImportService $importer): void
    {
        abort_unless(auth()->user()?->can('settings.manage-territory') ?? false, 403);

        $this->validate([
            'file' => ['required', 'file', 'max:15360', 'mimes:xlsx,csv'],
        ], [
            'file.required' => 'Seleccione un archivo (.xlsx o .csv).',
            'file.mimes' => 'Solo se admiten archivos Excel (.xlsx) o CSV UTF-8 (.csv).',
            'file.max' => 'El archivo no puede superar 15 MB.',
        ]);

        $uploaded = $this->file;
        $ext = strtolower((string) $uploaded->getClientOriginalExtension());

        try {
            $result = $importer->importFromPath((string) $uploaded->getRealPath(), $ext);
        } catch (RuntimeException $e) {
            $this->addError('file', $e->getMessage());

            return;
        }

        if ($result['inserted'] === 0 && $result['updated'] === 0) {
            $this->addError('file', 'No se importó ninguna fila. Compruebe que los datos empiezan en la fila 3 y que las columnas A–H coinciden con el listado DIVIPOLA (hoja «Municipios» en Excel).');

            return;
        }

        $this->lastImportResult = [
            'inserted' => (int) $result['inserted'],
            'updated' => (int) $result['updated'],
        ];

        $this->reset('file');
        $this->resetErrorBag();
        $this->resetPage();

        session()->flash(
            'success',
            "Importación completada: {$result['inserted']} nuevos, {$result['updated']} actualizados."
        );
    }

    #[Computed]
    public function kpiMunicipalities(): int
    {
        return ColombianMunicipality::query()->count();
    }

    #[Computed]
    public function kpiDepartments(): int
    {
        return (int) ColombianMunicipality::query()->distinct()->count('department_code');
    }

    #[Computed]
    public function kpiWithCoordinates(): int
    {
        return ColombianMunicipality::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->count();
    }

    #[Computed]
    public function kpiLastUpdated(): ?Carbon
    {
        $raw = ColombianMunicipality::query()->max('updated_at');

        return $raw ? Carbon::parse($raw) : null;
    }

    #[Computed]
    public function catalogComplete(): bool
    {
        return $this->kpiMunicipalities >= 1100;
    }

    public function render()
    {
        $query = ColombianMunicipality::query();

        if ($this->search !== '') {
            $query->search($this->search);
        }

        if ($this->departmentFilter !== '') {
            $query->where('department_code', $this->departmentFilter);
        }

        return view('livewire.settings.territory-import', [
            'municipalities' => $query
                ->orderBy('department_name')
                ->orderBy('municipality_name')
                ->paginate($this->perPage),
            'departments' => ColombianMunicipality::departmentsForSelect(),
            'kpiMunicipalities' => $this->kpiMunicipalities,
            'kpiDepartments' => $this->kpiDepartments,
            'kpiWithCoordinates' => $this->kpiWithCoordinates,
            'kpiLastUpdated' => $this->kpiLastUpdated,
            'catalogComplete' => $this->catalogComplete,
        ]);
    }
}
