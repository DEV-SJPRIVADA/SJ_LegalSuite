<?php

namespace App\Models;

use App\Enums\PlatformLevel;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'level_number',
        'subtitle',
    ];

    public function platformLevel(): ?PlatformLevel
    {
        return PlatformLevel::tryFromSlug($this->name);
    }

    public function displayTitle(): string
    {
        return $this->platformLevel()?->title() ?? (string) $this->name;
    }

    public function displaySubtitle(): ?string
    {
        if (filled($this->subtitle)) {
            return (string) $this->subtitle;
        }

        return $this->platformLevel()?->subtitle();
    }
}
