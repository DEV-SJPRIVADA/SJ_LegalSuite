<?php

namespace App\Services\Settings;

use App\Models\Disciplinary\DiligenceActaQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiligenceActaQuestionCatalogService
{
    /** @return Collection<int, DiligenceActaQuestion> */
    public function listOrdered(): Collection
    {
        return DiligenceActaQuestion::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(string $text): DiligenceActaQuestion
    {
        $text = $this->normalizeText($text);
        if ($text === '') {
            throw ValidationException::withMessages([
                'questionText' => 'Indique el texto de la pregunta.',
            ]);
        }

        $max = (int) DiligenceActaQuestion::query()->max('sort_order');

        return DiligenceActaQuestion::query()->create([
            'text' => $text,
            'sort_order' => $max + 1,
        ]);
    }

    public function update(DiligenceActaQuestion $question, string $text): DiligenceActaQuestion
    {
        $text = $this->normalizeText($text);
        if ($text === '') {
            throw ValidationException::withMessages([
                'questionText' => 'Indique el texto de la pregunta.',
            ]);
        }

        $question->forceFill(['text' => $text])->save();

        return $question->fresh();
    }

    public function delete(DiligenceActaQuestion $question): void
    {
        $question->delete();
        $this->resequence();
    }

    public function moveUp(DiligenceActaQuestion $question): void
    {
        $previous = DiligenceActaQuestion::query()
            ->where('sort_order', '<', $question->sort_order)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();

        if ($previous === null) {
            return;
        }

        $this->swapSortOrder($question, $previous);
    }

    public function moveDown(DiligenceActaQuestion $question): void
    {
        $next = DiligenceActaQuestion::query()
            ->where('sort_order', '>', $question->sort_order)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($next === null) {
            return;
        }

        $this->swapSortOrder($question, $next);
    }

    private function swapSortOrder(DiligenceActaQuestion $a, DiligenceActaQuestion $b): void
    {
        DB::transaction(function () use ($a, $b) {
            $orderA = $a->sort_order;
            $a->forceFill(['sort_order' => $b->sort_order])->save();
            $b->forceFill(['sort_order' => $orderA])->save();
        });
    }

    private function resequence(): void
    {
        $ids = DiligenceActaQuestion::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $index => $id) {
            DiligenceActaQuestion::query()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
