<?php

namespace App\Models\Disciplinary;

use App\Models\OrganizationalArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaryAgendaThread extends Model
{
    protected $fillable = [
        'disciplinary_case_id',
        'organizational_area_id',
        'opened_by',
        'coordination_started_at',
        'planning_replied_at',
        'coordination_status',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'coordination_started_at' => 'datetime',
            'planning_replied_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function organizationalArea(): BelongsTo
    {
        return $this->belongsTo(OrganizationalArea::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisciplinaryAgendaMessage::class, 'thread_id')
            ->orderBy('created_at');
    }

    public function hasPlanningReply(): bool
    {
        return $this->planning_replied_at !== null;
    }

    public function isOpen(): bool
    {
        return $this->coordination_status !== 'closed';
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }
}
