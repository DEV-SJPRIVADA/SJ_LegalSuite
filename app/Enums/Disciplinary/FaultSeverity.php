<?php

namespace App\Enums\Disciplinary;

enum FaultSeverity: string
{
    case LEVE = 'leve';
    case MEDIA = 'media';
    case GRAVE = 'grave';

    public function label(): string
    {
        return match ($this) {
            self::LEVE => 'Leve',
            self::MEDIA => 'Media',
            self::GRAVE => 'Grave',
        };
    }
}
