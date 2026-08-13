<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ColombianMunicipality extends Model
{
    protected $fillable = [
        'department_code',
        'department_name',
        'municipality_code',
        'municipality_name',
        'municipality_type',
        'longitude',
        'latitude',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'longitude' => 'float',
            'latitude' => 'float',
        ];
    }

    /**
     * @return Collection<string, Collection<int, array{code: string, name: string}>>
     */
    public static function groupedByDepartmentForSelect(): Collection
    {
        return once(function () {
            return static::query()
                ->orderBy('department_name')
                ->orderBy('municipality_name')
                ->get(['department_name', 'municipality_code', 'municipality_name'])
                ->groupBy('department_name')
                ->map(fn (Collection $rows) => $rows->map(fn (self $m) => [
                    'code' => $m->municipality_code,
                    'name' => $m->municipality_name,
                ])->values());
        });
    }

    /**
     * @return Collection<int, array{code: string, name: string}>
     */
    public static function departmentsForSelect(): Collection
    {
        return once(function () {
            return static::query()
                ->select(['department_code', 'department_name'])
                ->distinct()
                ->orderBy('department_name')
                ->get()
                ->map(fn (self $row) => [
                    'code' => (string) $row->department_code,
                    'name' => (string) $row->department_name,
                ])
                ->values();
        });
    }

    public static function departmentName(?string $departmentCode): ?string
    {
        if (! filled($departmentCode)) {
            return null;
        }

        return static::query()
            ->where('department_code', $departmentCode)
            ->value('department_name');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(' ', '%', $term).'%';

        return $query->where(function (Builder $inner) use ($like, $term): void {
            $inner->where('municipality_name', 'like', $like)
                ->orWhere('department_name', 'like', $like)
                ->orWhere('municipality_code', 'like', $like);

            $digits = preg_replace('/\D+/', '', $term) ?? '';
            if ($digits !== '') {
                $inner->orWhere('municipality_code', 'like', '%'.$digits.'%')
                    ->orWhere('department_code', 'like', '%'.$digits.'%');
            }
        });
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
