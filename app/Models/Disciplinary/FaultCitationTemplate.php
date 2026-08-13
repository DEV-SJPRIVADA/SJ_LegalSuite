<?php

namespace App\Models\Disciplinary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaultCitationTemplate extends Model
{
    protected $fillable = [
        'fault_id',
    ];

    public function fault(): BelongsTo
    {
        return $this->belongsTo(Fault::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(FaultCitationTemplateArticle::class)->orderBy('sort_order');
    }
}
