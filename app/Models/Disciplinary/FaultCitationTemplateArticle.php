<?php

namespace App\Models\Disciplinary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FaultCitationTemplateArticle extends Model
{
    protected $fillable = [
        'fault_citation_template_id',
        'citation_statute_article_id',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FaultCitationTemplate::class, 'fault_citation_template_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(CitationStatuteArticle::class, 'citation_statute_article_id');
    }

    public function numerals(): BelongsToMany
    {
        return $this->belongsToMany(
            CitationStatuteNumeral::class,
            'fault_citation_template_numerals',
            'fault_citation_template_article_id',
            'citation_statute_numeral_id',
        );
    }
}
