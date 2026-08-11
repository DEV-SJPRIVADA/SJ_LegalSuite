<?php

namespace App\Livewire\Settings;

use App\Models\Disciplinary\Fault;
use App\Services\Settings\CitationFaultTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ajustes · Citación · Artículos · SJ LegalSuite')]
class CitationArticlesIndex extends Component
{
    public bool $showManageModal = false;

    public ?int $editingFaultId = null;

    /** @var list<array{article_number: string, numerals: string}> */
    public array $editingBlocks = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('settings.manage-citation-articles') ?? false, 403);
    }

    public function openManageModal(int $faultId): void
    {
        abort_unless(auth()->user()?->can('settings.manage-citation-articles') ?? false, 403);

        $fault = Fault::query()->findOrFail($faultId);
        $this->editingFaultId = $fault->id;
        $blocks = app(CitationFaultTemplateService::class)->templateBlocksForFault($fault);
        $this->editingBlocks = array_map(
            static fn (array $block): array => [
                'article_number' => (string) ($block['article_number'] ?? ''),
                'numerals' => implode(', ', $block['numerals'] ?? []),
            ],
            $blocks,
        );
        $this->showManageModal = true;
    }

    public function closeManageModal(): void
    {
        $this->showManageModal = false;
        $this->editingFaultId = null;
        $this->editingBlocks = [];
        $this->resetErrorBag();
    }

    public function addEditingArticleRow(): void
    {
        $this->editingBlocks[] = ['article_number' => '', 'numerals' => ''];
    }

    public function removeEditingArticleRow(int $index): void
    {
        if (! isset($this->editingBlocks[$index])) {
            return;
        }

        unset($this->editingBlocks[$index]);
        $this->editingBlocks = array_values($this->editingBlocks);
    }

    public function saveFaultTemplate(CitationFaultTemplateService $service): void
    {
        abort_unless(auth()->user()?->can('settings.manage-citation-articles') ?? false, 403);

        $fault = Fault::query()->findOrFail($this->editingFaultId);
        $blocks = [];

        foreach ($this->editingBlocks as $index => $row) {
            $articleNumber = trim((string) ($row['article_number'] ?? ''));
            if ($articleNumber === '') {
                continue;
            }

            $numerals = array_values(array_filter(array_map(
                static fn (string $part): string => trim($part),
                preg_split('/\s*,\s*/', (string) ($row['numerals'] ?? '')) ?: [],
            )));

            $blocks[] = [
                'article_number' => $articleNumber,
                'numerals' => $numerals,
            ];
        }

        if ($blocks === []) {
            $service->clearTemplateForFault($fault);
        } else {
            $service->saveTemplateForFault($fault, $blocks);
        }

        $this->closeManageModal();
        session()->flash('success', 'Plantilla de artículos guardada para la falta seleccionada.');
    }

    public function render(CitationFaultTemplateService $service)
    {
        return view('livewire.settings.citation-articles-index', [
            'faults' => $service->faultsWithTemplateSummary(),
        ]);
    }
}
