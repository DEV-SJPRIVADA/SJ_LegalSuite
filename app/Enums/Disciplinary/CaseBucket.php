<?php

namespace App\Enums\Disciplinary;

/**
 * Agrupación de alto nivel de los estados del caso para los KPIs del dashboard.
 */
enum CaseBucket: string
{
    case PENDIENTE = 'pendiente';
    case EN_PROCESO = 'en_proceso';
    case FINALIZADO = 'finalizado';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_PROCESO => 'En proceso',
            self::FINALIZADO => 'Finalizado',
        };
    }
}
