<?php

namespace Database\Seeders;

use App\Models\Disciplinary\CitationStatuteArticle;
use App\Models\Disciplinary\CitationStatuteNumeral;
use App\Models\Disciplinary\Fault;
use App\Models\Disciplinary\FaultCitationTemplate;
use App\Models\Disciplinary\FaultCitationTemplateArticle;
use Illuminate\Database\Seeder;

class CitationFaultTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'F-001' => [
                '74' => ['1', '2', '3', '6', '9', '12', '16', '20', '21', '22', '28', '29', '30', '38', '44', '45', '46', '47', '50', '52', '60', '85'],
                '76' => ['32', '35', '36', '42', '44', '49', '62', '96', '102'],
                '79' => ['5', '6.1', '6.16', '6.20', '6.41', '6.42', '6.49', '6.51', '6.54', '6.60', '6.61'],
            ],
            'F-002' => [
                '74' => ['1', '2', '6', '9', '12', '16', '17', '20', '21', '22', '28', '29', '30', '31', '33', '38', '44', '45', '46', '47', '50', '53', '60', '70', '85'],
                '76' => ['32', '34', '35', '36', '37', '42', '44', '49', '53', '59', '62', '63', '64', '67', '77', '80', '96', '100', '102'],
                '79' => ['5', '6.1', '6.6', '6.16', '6.20', '6.22', '6.29', '6.38', '6.41', '6.42', '6.49', '6.51', '6.54', '6.60', '6.61', '6.67'],
            ],
            'F-003' => [
                '74' => ['1', '2', '3', '6', '9', '12', '17', '20', '21', '22', '30', '38', '44', '45', '46', '53', '60', '63', '85'],
                '76' => ['16', '32', '33', '35', '36', '37', '42', '43', '44', '46', '49', '59', '63', '96', '102'],
                '79' => ['5', '6.1', '6.7', '6.16', '6.20', '6.29', '6.38', '6.41', '6.42', '6.46', '6.49', '6.51', '6.60', '6.61'],
            ],
            'F-004' => [
                '55' => ['1', '2', '9', '20', '21', '22', '30', '38', '45', '60', '85'],
                '57' => ['17', '32', '35', '42', '63', '75', '96', '102'],
                '60' => ['5', '6.2', '6.11', '6.16', '6.20', '6.38', '6.41', '6.42', '6.49', '6.61'],
            ],
            'F-005' => [
                '74' => ['1', '2', '6', '9', '12', '19', '20', '21', '22', '25', '30', '38', '44', '45', '46', '60', '61', '80', '85'],
                '76' => ['27', '32', '35', '36', '42', '43', '59', '96', '102'],
                '79' => ['5', '6.12', '6.16', '6.20', '6.29', '6.41', '6.42', '6.49', '6.51', '6.60', '6.61'],
            ],
            'F-007' => [
                '74' => ['1', '2', '9', '16', '21', '22', '28', '30', '31', '38', '45', '47', '50', '81'],
                '76' => ['26', '32', '100', '102'],
                '79' => ['3', '5', '6.20', '6.49', '6.51', '6.61', '6.67'],
            ],
            'F-010' => [
                '74' => ['1', '2', '6', '9', '12', '19', '20', '21', '22', '25', '30', '38', '44', '45', '46', '60', '61', '80', '85'],
                '76' => ['27', '32', '35', '36', '42', '43', '59', '96', '102'],
                '79' => ['5', '6.12', '6.16', '6.20', '6.29', '6.41', '6.42', '6.49', '6.51', '6.60', '6.61'],
            ],
        ];

        foreach ($templates as $faultCode => $articles) {
            $fault = Fault::query()->where('code', $faultCode)->first();
            if ($fault === null) {
                continue;
            }

            $template = FaultCitationTemplate::query()->updateOrCreate(['fault_id' => $fault->id]);
            $template->articles()->each(function (FaultCitationTemplateArticle $link): void {
                $link->numerals()->detach();
                $link->delete();
            });

            $sort = 0;
            foreach ($articles as $articleNumber => $numeralCodes) {
                $article = $this->ensureArticle((string) $articleNumber);
                $link = FaultCitationTemplateArticle::query()->create([
                    'fault_citation_template_id' => $template->id,
                    'citation_statute_article_id' => $article->id,
                    'sort_order' => ++$sort,
                ]);

                $numeralIds = [];
                $numeralSort = 0;
                foreach ($numeralCodes as $code) {
                    $numeral = CitationStatuteNumeral::query()->updateOrCreate(
                        [
                            'citation_statute_article_id' => $article->id,
                            'code' => (string) $code,
                        ],
                        ['sort_order' => ++$numeralSort],
                    );
                    $numeralIds[] = $numeral->id;
                }

                if ($numeralIds !== []) {
                    $link->numerals()->sync($numeralIds);
                }
            }
        }
    }

    private function ensureArticle(string $number): CitationStatuteArticle
    {
        return CitationStatuteArticle::query()->updateOrCreate(
            ['number' => $number],
            [
                'clause_suffix' => null,
                'sort_order' => (int) $number,
            ],
        );
    }
}
