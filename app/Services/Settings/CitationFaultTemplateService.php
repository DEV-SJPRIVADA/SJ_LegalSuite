<?php

namespace App\Services\Settings;

use App\Models\Disciplinary\CitationStatuteArticle;
use App\Models\Disciplinary\CitationStatuteNumeral;
use App\Models\Disciplinary\Fault;
use App\Models\Disciplinary\FaultCitationTemplate;
use App\Models\Disciplinary\FaultCitationTemplateArticle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CitationFaultTemplateService
{
    /** @return Collection<int, Fault> */
    public function faultsWithTemplateSummary(): Collection
    {
        return Fault::query()
            ->ordered()
            ->with(['citationTemplate.articles.numerals', 'citationTemplate.articles.article'])
            ->get();
    }

    /** @return list<array{article_number: string, numerals: list<string>}> */
    public function templateBlocksForFault(Fault $fault): array
    {
        $template = $fault->citationTemplate()
            ->with(['articles.article', 'articles.numerals'])
            ->first();

        if ($template === null) {
            return [];
        }

        return $this->blocksFromTemplate($template);
    }

    /**
     * @param  list<array{article_number: string, numerals: list<string>}>  $blocks
     */
    public function saveTemplateForFault(Fault $fault, array $blocks): void
    {
        DB::transaction(function () use ($fault, $blocks): void {
            $template = FaultCitationTemplate::query()->firstOrCreate(['fault_id' => $fault->id]);

            $template->articles()->each(function (FaultCitationTemplateArticle $link): void {
                $link->numerals()->detach();
                $link->delete();
            });

            $sort = 0;
            foreach ($blocks as $block) {
                $articleNumber = trim((string) ($block['article_number'] ?? ''));
                if ($articleNumber === '') {
                    continue;
                }

                $article = $this->ensureArticle($articleNumber);
                $link = FaultCitationTemplateArticle::query()->create([
                    'fault_citation_template_id' => $template->id,
                    'citation_statute_article_id' => $article->id,
                    'sort_order' => ++$sort,
                ]);

                $numeralIds = [];
                $numeralSort = 0;
                foreach ($block['numerals'] ?? [] as $code) {
                    $normalized = trim((string) $code);
                    if ($normalized === '') {
                        continue;
                    }

                    $numeral = CitationStatuteNumeral::query()->updateOrCreate(
                        [
                            'citation_statute_article_id' => $article->id,
                            'code' => $normalized,
                        ],
                        ['sort_order' => ++$numeralSort],
                    );
                    $numeralIds[] = $numeral->id;
                }

                if ($numeralIds !== []) {
                    $link->numerals()->sync($numeralIds);
                }
            }
        });
    }

    public function clearTemplateForFault(Fault $fault): void
    {
        $template = $fault->citationTemplate()->first();
        if ($template === null) {
            return;
        }

        DB::transaction(function () use ($template): void {
            $template->articles()->each(function (FaultCitationTemplateArticle $link): void {
                $link->numerals()->detach();
                $link->delete();
            });
            $template->delete();
        });
    }

    /** @return Collection<int, CitationStatuteArticle> */
    public function catalogArticles(): Collection
    {
        return CitationStatuteArticle::query()
            ->orderBy('sort_order')
            ->orderBy('number')
            ->with('numerals')
            ->get();
    }

    public function ensureArticle(string $number, ?string $clauseSuffix = null): CitationStatuteArticle
    {
        $article = CitationStatuteArticle::query()->firstOrNew(['number' => trim($number)]);
        if (! $article->exists) {
            $article->sort_order = (int) preg_replace('/\D/', '', $number) ?: 0;
        }
        if ($clauseSuffix !== null) {
            $article->clause_suffix = $clauseSuffix;
        }
        $article->save();

        return $article;
    }

    /** @return list<array{article_number: string, numerals: list<string>}> */
    private function blocksFromTemplate(FaultCitationTemplate $template): array
    {
        $blocks = [];
        foreach ($template->articles as $link) {
            $articleNumber = (string) ($link->article?->number ?? '');
            if ($articleNumber === '') {
                continue;
            }

            $blocks[] = [
                'article_number' => $articleNumber,
                'numerals' => $link->numerals
                    ->sortBy('sort_order')
                    ->pluck('code')
                    ->values()
                    ->all(),
            ];
        }

        return $blocks;
    }
}
