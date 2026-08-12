<?php

namespace App\Models\Disciplinary;

use Illuminate\Database\Eloquent\Model;

class DiligenceActaQuestion extends Model
{
    protected $fillable = [
        'text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
