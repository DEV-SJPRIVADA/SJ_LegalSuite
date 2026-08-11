<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citation_statute_articles', function (Blueprint $table) {
            $table->id();
            $table->string('number', 16)->unique();
            $table->string('clause_suffix')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('citation_statute_numerals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('citation_statute_article_id');
            $table->string('code', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('citation_statute_article_id', 'csn_article_fk')
                ->references('id')
                ->on('citation_statute_articles')
                ->cascadeOnDelete();
            $table->unique(['citation_statute_article_id', 'code'], 'csn_article_code_unique');
        });

        Schema::create('fault_citation_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fault_id')->unique();
            $table->timestamps();

            $table->foreign('fault_id', 'fct_fault_fk')
                ->references('id')
                ->on('faults')
                ->cascadeOnDelete();
        });

        Schema::create('fault_citation_template_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fault_citation_template_id');
            $table->unsignedBigInteger('citation_statute_article_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('fault_citation_template_id', 'fcta_template_fk')
                ->references('id')
                ->on('fault_citation_templates')
                ->cascadeOnDelete();
            $table->foreign('citation_statute_article_id', 'fcta_article_fk')
                ->references('id')
                ->on('citation_statute_articles')
                ->cascadeOnDelete();
            $table->unique(
                ['fault_citation_template_id', 'citation_statute_article_id'],
                'fcta_template_article_unique',
            );
        });

        Schema::create('fault_citation_template_numerals', function (Blueprint $table) {
            $table->unsignedBigInteger('fault_citation_template_article_id');
            $table->unsignedBigInteger('citation_statute_numeral_id');

            $table->foreign('fault_citation_template_article_id', 'fctn_tpl_article_fk')
                ->references('id')
                ->on('fault_citation_template_articles')
                ->cascadeOnDelete();
            $table->foreign('citation_statute_numeral_id', 'fctn_numeral_fk')
                ->references('id')
                ->on('citation_statute_numerals')
                ->cascadeOnDelete();

            $table->primary(
                ['fault_citation_template_article_id', 'citation_statute_numeral_id'],
                'fctn_primary',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fault_citation_template_numerals');
        Schema::dropIfExists('fault_citation_template_articles');
        Schema::dropIfExists('fault_citation_templates');
        Schema::dropIfExists('citation_statute_numerals');
        Schema::dropIfExists('citation_statute_articles');
    }
};
