<?php

namespace App\Models\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Directorio corporativo de funcionarios (copia local en BD de LegalSuite).
 * Se sincroniza desde Agendamiento (agenda_users).
 */
class DirectoryUser extends Model
{
    protected $table = 'directory_users';

    protected $fillable = [
        'name',
        'email',
        'is_active',
        'source',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'external_id' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner->where('name', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
