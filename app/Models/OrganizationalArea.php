<?php

namespace App\Models;

use App\Enums\UserArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrganizationalArea extends Model
{
    protected $fillable = [
        'slug',
        'name',
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

    public function jobPositions(): HasMany
    {
        return $this->hasMany(JobPosition::class);
    }

    /**
     * Slug por defecto al crear desde interfaz — debe poder enlazar el enum legado cuando aplique.
     */
    public static function slugFromLabel(string $name): string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? substr($slug, 0, 64) : 'area';
    }

    /** Etiqueta legada cuando el slug coincide con un caso del enum `UserArea`. */
    public function legacyAreaEnum(): ?UserArea
    {
        return UserArea::tryFrom($this->slug);
    }
}
