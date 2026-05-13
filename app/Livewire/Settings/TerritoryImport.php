<?php

namespace App\Livewire\Settings;

use App\Models\ColombianMunicipality;
use App\Services\Settings\ColombianMunicipalityImportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Ajustes · Territorio · SJ LegalSuite')]
class TerritoryImport extends Component
{
    use WithFileUploads;

    /** @var mixed */
    public $file = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-territory') ?? false, 403);
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

        $this->reset('file');
        $this->resetErrorBag();

        session()->flash(
            'success',
            "Importación completada: {$result['inserted']} nuevos, {$result['updated']} actualizados."
        );
    }

    public function render()
    {
        return view('livewire.settings.territory-import', [
            'municipalityCount' => ColombianMunicipality::query()->count(),
        ]);
    }
}
