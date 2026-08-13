<?php

namespace App\Enums\Licitaciones;

enum DocumentRevisionStatus: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
    case Reemplazado = 'reemplazado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de revisión',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Requiere corrección',
            self::Reemplazado => 'Reemplazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
            self::Aprobado => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
            self::Rechazado => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
            self::Reemplazado => 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-400',
        };
    }

    public function isActionable(): bool
    {
        return $this === self::Pendiente || $this === self::Rechazado;
    }
}
