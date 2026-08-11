<?php

namespace App\Models\Disciplinary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitationStatuteNumeral extends Model
{
    protected $fillable = [
        'citation_statute_article_id',
        'code',
        'sort_order',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(CitationStatuteArticle::class, 'citation_statute_article_id');
    }
}
