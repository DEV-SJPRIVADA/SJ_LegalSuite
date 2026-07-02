<?php

namespace App\Enums\Licitaciones;

enum RequestStatus: string
{
    case Recibido = 'recibido';
    case EnTramite = 'en_tramite';
    case Respondido = 'respondido';
    case Vencido = 'vencido';
    case Enviado = 'enviado';
    case Rechazado = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::Recibido => 'Recibido',
            self::EnTramite => 'En trámite',
            self::Respondido => 'Respondido',
            self::Vencido => 'Vencido',
            self::Enviado => 'Enviado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Recibido => 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300',
            self::EnTramite => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
            self::Respondido, self::Enviado => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
            self::Vencido => 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300',
            self::Rechazado => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
        };
    }
}
