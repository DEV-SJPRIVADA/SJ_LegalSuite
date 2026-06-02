<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\AgendaMessageKind;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaryAgendaMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'user_id',
        'message_kind',
        'body',
        'proposed_slots',
        'notification_payload',
    ];

    protected function casts(): array
    {
        return [
            'message_kind' => AgendaMessageKind::class,
            'proposed_slots' => 'array',
            'notification_payload' => 'array',
        ];
    }

    /** @return list<array{date: string, time?: string|null, notes?: string|null}> */
    public function normalizedProposedSlots(): array
    {
        $slots = $this->proposed_slots ?? [];

        return is_array($slots) ? array_values($slots) : [];
    }

    /** @return array<string, mixed> */
    public function normalizedNotificationPayload(): array
    {
        $payload = $this->notification_payload ?? [];

        return is_array($payload) ? $payload : [];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryAgendaThread::class, 'thread_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DisciplinaryAgendaAttachment::class, 'agenda_message_id');
    }
}
