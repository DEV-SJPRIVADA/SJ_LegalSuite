<?php

namespace App\Models\Disciplinary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CitationStatuteArticle extends Model
{
    protected $fillable = [
        'number',
        'clause_suffix',
        'sort_order',
    ];

    public function numerals(): HasMany
    {
        return $this->hasMany(CitationStatuteNumeral::class)->orderBy('sort_order')->orderBy('code');
    }
}
