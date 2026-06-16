<?php

namespace App\Enums\Disciplinary;

enum DiligenceAttendance: string
{
    case ATTENDED = 'attended';
    case ABSENT = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::ATTENDED => 'Asistió',
            self::ABSENT => 'No asistió',
        };
    }
}
