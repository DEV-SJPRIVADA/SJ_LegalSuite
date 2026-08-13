<?php

namespace Tests\Unit\Support\Disciplinary;

use App\Support\Disciplinary\DecisionStatuteArticles;
use PHPUnit\Framework\TestCase;

class DecisionStatuteArticlesTest extends TestCase
{
    public function test_resolves_from_fo_gj_03_blocks_not_only_55_57_60(): void
    {
        $blocks = DecisionStatuteArticles::resolve([
            'statute_articles' => [
                ['article_number' => '74', 'numerals' => ['1', '2', '6']],
                ['article_number' => '76', 'numerals' => '32, 35'],
                ['article_number' => '79', 'numerals' => '5'],
            ],
        ]);

        $this->assertCount(3, $blocks);
        $this->assertSame('74', $blocks[0]['article_number']);
        $this->assertSame('1, 2, 6', $blocks[0]['numerals']);
        $this->assertSame('76', $blocks[1]['article_number']);
        $this->assertSame('32, 35', $blocks[1]['numerals']);
    }

    public function test_empty_decision_payload_falls_back_to_fo03(): void
    {
        $blocks = DecisionStatuteArticles::resolve(
            [
                'statute_articles' => [
                    ['article_number' => '55', 'numerals' => '1, 9'],
                ],
            ],
            [
                'articles_55' => '',
                'statute_articles' => [
                    ['article_number' => '55', 'numerals' => ''],
                ],
            ],
        );

        $this->assertSame('1, 9', $blocks[0]['numerals']);
    }

    public function test_legacy_three_articles_when_fo03_empty(): void
    {
        $blocks = DecisionStatuteArticles::resolve([], [
            'articles_55' => '1',
            'articles_57' => '2',
            'articles_60' => '3',
        ]);

        $this->assertSame(['55', '57', '60'], array_column($blocks, 'article_number'));
        $this->assertSame('1', DecisionStatuteArticles::numeralsFor($blocks, '55'));
    }

    public function test_reads_fo03_legacy_article_keys(): void
    {
        $blocks = DecisionStatuteArticles::resolve([
            'article_66_numerals' => '1, 4',
            'article_76_numerals' => '32',
        ]);

        $this->assertCount(2, $blocks);
        $this->assertSame('66', $blocks[0]['article_number']);
        $this->assertSame('1, 4', $blocks[0]['numerals']);
        $this->assertSame('76', $blocks[1]['article_number']);
    }

    public function test_does_not_invent_empty_55_57_60(): void
    {
        $blocks = DecisionStatuteArticles::resolve([]);

        $this->assertSame([], $blocks);
    }
}
