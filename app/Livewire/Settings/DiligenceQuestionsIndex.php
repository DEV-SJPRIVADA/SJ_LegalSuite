<?php

namespace App\Livewire\Settings;

use App\Models\Disciplinary\DiligenceActaQuestion;
use App\Services\Settings\DiligenceActaQuestionCatalogService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ajustes · Preguntas diligencia · SJ LegalSuite')]
class DiligenceQuestionsIndex extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $questionText = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);
        $this->editingId = null;
        $this->questionText = '';
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);
        $question = DiligenceActaQuestion::query()->findOrFail($id);
        $this->editingId = $question->id;
        $this->questionText = $question->text;
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingId = null;
        $this->questionText = '';
        $this->resetErrorBag();
    }

    public function saveQuestion(DiligenceActaQuestionCatalogService $catalog): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);

        try {
            if ($this->editingId !== null) {
                $question = DiligenceActaQuestion::query()->findOrFail($this->editingId);
                $catalog->update($question, $this->questionText);
                session()->flash('success', 'Pregunta actualizada.');
            } else {
                $catalog->create($this->questionText);
                session()->flash('success', 'Pregunta creada.');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->closeFormModal();
    }

    public function deleteQuestion(int $id, DiligenceActaQuestionCatalogService $catalog): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);
        $question = DiligenceActaQuestion::query()->findOrFail($id);
        $catalog->delete($question);
        session()->flash('success', 'Pregunta eliminada. Las actas ya generadas conservan su texto.');
    }

    public function moveUp(int $id, DiligenceActaQuestionCatalogService $catalog): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);
        $catalog->moveUp(DiligenceActaQuestion::query()->findOrFail($id));
    }

    public function moveDown(int $id, DiligenceActaQuestionCatalogService $catalog): void
    {
        abort_unless(auth()->user()?->can('settings.manage-diligence-questions') ?? false, 403);
        $catalog->moveDown(DiligenceActaQuestion::query()->findOrFail($id));
    }

    public function render(DiligenceActaQuestionCatalogService $catalog)
    {
        return view('livewire.settings.diligence-questions-index', [
            'questions' => $catalog->listOrdered(),
        ]);
    }
}
