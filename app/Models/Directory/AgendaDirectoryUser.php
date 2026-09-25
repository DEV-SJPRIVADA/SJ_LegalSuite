<?php

namespace App\Models\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Funcionarios del directorio corporativo (tabla agenda_users de Agendamiento de espacios).
 * Solo lectura desde SJ LegalSuite.
 */
class AgendaDirectoryUser extends Model
{
    protected $connection = 'agendamiento';

    protected $table = 'agenda_users';

    protected $fillable = [
        'name',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
