<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $fillable = [
        'organizational_area_id',
        'name',
        'permission_role_name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function organizationalArea(): BelongsTo
    {
        return $this->belongsTo(OrganizationalArea::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
