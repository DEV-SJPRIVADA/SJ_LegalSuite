<?php

namespace App\Enums;

enum EmployeeScope: string
{
    case Administrativo = 'administrativo';
    case Operativo = 'operativo';

    public function label(): string
    {
        return match ($this) {
            self::Administrativo => 'Administrativo',
            self::Operativo => 'Operativo',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $scope) => [$scope->value => $scope->label()])
            ->all();
    }

    public static function tryFromLabel(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        return self::tryFrom($normalized);
    }
}
