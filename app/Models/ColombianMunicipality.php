<?php

namespace App\Models;

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
}
