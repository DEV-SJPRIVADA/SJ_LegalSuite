<?php

namespace App\Livewire\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Settings\OrganizationLetterheadService;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Formatos disciplinarios · SJ LegalSuite')]
class FormatsCatalog extends Component
{
    use WithFileUploads;

    /** Vista previa modal del PDF en blanco (archivo estático o HTML→PDF tamaño carta). */
    public ?string $activeFormPreview = null;

    /** @var mixed */
    public $letterheadFile = null;

    public function mount(): void
    {
        Gate::authorize('viewOfficialForms', DisciplinaryCase::class);
    }

    public function openFormPreview(string $code): void
    {
        $this->activeFormPreview = $code;
    }

    public function closeFormPreview(): void
    {
        $this->activeFormPreview = null;
    }

    public function uploadLetterhead(OrganizationLetterheadService $letterheads): void
    {
        Gate::authorize('manageOfficialLetterhead', DisciplinaryCase::class);

        $this->validate([
            'letterheadFile' => ['required', 'file', 'max:8192', 'mimes:png,jpg,jpeg'],
        ], [
            'letterheadFile.required' => 'Seleccione una imagen PNG o JPEG.',
            'letterheadFile.mimes' => 'Solo se admiten imágenes PNG o JPEG.',
            'letterheadFile.max' => 'La imagen no puede superar 8 MB.',
        ]);

        $letterheads->storeImage($this->letterheadFile);
        $this->reset('letterheadFile');
        session()->flash('success', 'Membrete cargado. Se usará en el acta de comité disciplinario.');
    }

    public function removeLetterhead(OrganizationLetterheadService $letterheads): void
    {
        Gate::authorize('manageOfficialLetterhead', DisciplinaryCase::class);

        $letterheads->removeImage();
        $this->reset('letterheadFile');
        session()->flash('success', 'Membrete eliminado. El acta de comité volverá al encabezado estándar.');
    }

    public function render()
    {
        $letterheads = app(OrganizationLetterheadService::class);

        return view('livewire.disciplinary.formats-catalog', [
            'forms' => OfficialFormsCatalog::all(),
            'letterheadConfigured' => $letterheads->hasImage(),
            'letterheadOriginalName' => $letterheads->originalFileName(),
            'letterheadUploadedAt' => $letterheads->uploadedAtLabel(),
            'canManageLetterhead' => Gate::allows('manageOfficialLetterhead', DisciplinaryCase::class),
        ]);
    }
}
