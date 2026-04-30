<?php

namespace App\Models;

use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Personal disciplinable. Está desacoplado de User para soportar histórico
 * legal aunque el empleado deje de existir, y para permitir integración con
 * sistemas externos (ej: SJ_Armory) vía external_id.
 */
class Personnel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personnel';

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'phone',
        'email',
        'position',
        'city',
        'sede',
        'hired_at',
        'is_active',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(' ', '%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('document_number', 'like', $like);
        });
    }
}
