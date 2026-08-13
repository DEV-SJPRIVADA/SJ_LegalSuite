<?php

namespace App\Models;

use App\Enums\EmployeeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmployeeJobPosition extends Model
{
    /** @var array<string, string> */
    private const IMPORT_ALIASES = [
        'escolta 6x1' => 'escolta',
        'guarda de seguridad motorizado (c/a)' => 'guarda motorizado (c/a)',
        'guarda de seguridad motorizado (s/a)' => 'guarda motorizado (s/a)',
    ];

    protected $fillable = [
        'slug',
        'name',
        'is_guarda',
        'employee_scope',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_guarda' => 'boolean',
            'is_active' => 'boolean',
            'employee_scope' => EmployeeScope::class,
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeGuarda(Builder $query): Builder
    {
        return $query->where('is_guarda', true);
    }

    public static function slugFromLabel(string $name): string
    {
        return Str::slug($name) ?: 'cargo';
    }

    public static function normalizeName(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $v);
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return self::IMPORT_ALIASES[$v] ?? $v;
    }

    public static function resolveIdFromLabel(string $label): ?int
    {
        $normalized = self::normalizeName($label);
        if ($normalized === '') {
            return null;
        }

        $match = static::query()
            ->active()
            ->get(['id', 'name'])
            ->first(fn (self $position) => self::normalizeName($position->name) === $normalized);

        if ($match instanceof self) {
            return (int) $match->id;
        }

        if (str_contains($normalized, 'guarda')) {
            $guardaId = static::query()->active()->where('name', 'GUARDA DE SEGURIDAD')->value('id');

            return $guardaId ? (int) $guardaId : null;
        }

        if (str_contains($normalized, 'escolta')) {
            $escoltaId = static::query()->active()->where('name', 'ESCOLTA')->value('id');

            return $escoltaId ? (int) $escoltaId : null;
        }

        return null;
    }
}
