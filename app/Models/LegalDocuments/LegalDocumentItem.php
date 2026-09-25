<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegalDocumentItem extends Model
{
    protected $table = 'legal_document_items';

    protected $fillable = [
        'folder_id',
        'code',
        'group_title',
        'title',
        'issued_by',
        'issued_on',
        'frequency',
        'renew_on',
        'renew_label',
        'observations',
        'source',
        'is_active',
        'last_reminder_at',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'renew_on' => 'date',
            'is_active' => 'boolean',
            'last_reminder_at' => 'datetime',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentFolder::class, 'folder_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function currentFile(): HasOne
    {
        return $this->hasOne(LegalDocumentFile::class, 'item_id');
    }

    public function displayTitle(): string
    {
        if ($this->group_title) {
            return $this->group_title.' · '.$this->title;
        }

        return $this->title;
    }

    public function isDueForRenewal(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->renew_on === null) {
            return false;
        }

        return $this->renew_on->startOfDay()->lte(now()->startOfDay());
    }

    public function needsReminder(): bool
    {
        if (! $this->isDueForRenewal()) {
            return false;
        }

        $folder = $this->folder;
        if (! $folder || $folder->exclude_reminders) {
            return false;
        }

        // Si ya hay archivo vigente subido después de la fecha de renovación, no recordar.
        $file = $this->currentFile;
        if ($file && $file->created_at && $this->renew_on && $file->created_at->gte($this->renew_on->startOfDay())) {
            return false;
        }

        if ($this->last_reminder_at === null) {
            return true;
        }

        return $this->last_reminder_at->lte(now()->subHour());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
