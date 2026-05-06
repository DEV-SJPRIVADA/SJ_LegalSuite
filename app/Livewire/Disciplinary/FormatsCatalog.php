<?php

namespace App\Livewire\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Formatos disciplinarios · SJ LegalSuite')]
class FormatsCatalog extends Component
{
    /** Vista previa modal del PDF en blanco (archivo estático o HTML→PDF tamaño carta). */
    public ?string $activeFormPreview = null;

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

    public function render()
    {
        return view('livewire.disciplinary.formats-catalog', [
            'forms' => OfficialFormsCatalog::all(),
        ]);
    }
}
