<?php

namespace App\Enums;

enum PlatformLevel: string
{
    case Nivel1 = 'nivel1';
    case Nivel2 = 'nivel2';
    case Nivel3 = 'nivel3';
    case Nivel4 = 'nivel4';
    case Nivel5 = 'nivel5';
    case Nivel6 = 'nivel6';
    case Nivel7 = 'nivel7';
    case Nivel8 = 'nivel8';
    case Nivel9 = 'nivel9';

    public function number(): int
    {
        return (int) substr($this->value, 5);
    }

    public function title(): string
    {
        return 'Nivel '.$this->number();
    }

    public function subtitle(): string
    {
        return match ($this) {
            self::Nivel1 => 'Administrador plataforma',
            self::Nivel2 => 'Dirección operaciones',
            self::Nivel3 => 'Planeación',
            self::Nivel4 => 'Área administrativa',
            self::Nivel5 => 'Auditoría',
            self::Nivel6 => 'Abogado asignado',
            self::Nivel7 => 'Supervisor de campo',
            self::Nivel8 => 'Operador central',
            self::Nivel9 => 'Programador de fechas',
        };
    }

    public function requiresAuthorizedCities(): bool
    {
        return in_array($this, [self::Nivel7, self::Nivel8], true);
    }

    public static function tryFromSlug(?string $slug): ?self
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return self::tryFrom($slug);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->title().' — '.$case->subtitle();
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        return [
            'admin' => self::Nivel1->value,
            'operaciones' => self::Nivel2->value,
            'planeacion' => self::Nivel3->value,
            'administrativa' => self::Nivel4->value,
            'auditor' => self::Nivel5->value,
            'abogado' => self::Nivel6->value,
            'supervisor' => self::Nivel7->value,
            'operador' => self::Nivel8->value,
            'programador' => self::Nivel9->value,
        ];
    }
}
