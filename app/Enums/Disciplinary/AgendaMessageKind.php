<?php

namespace App\Enums\Disciplinary;

enum AgendaMessageKind: string
{
    case GENERAL = 'general';
    case LAWYER_REQUEST = 'lawyer_request';
    case PLANNING_RESPONSE = 'planning_response';
    case LAWYER_NOTIFICATION_REQUEST = 'lawyer_notification_request';
    case NOTIFICATION_COORDINATION = 'notification_coordination';
}
