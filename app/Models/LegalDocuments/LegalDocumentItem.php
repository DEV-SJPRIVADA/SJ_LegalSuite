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

        return $this->renew_on->copy()->startOfDay()->lte(now()->startOfDay());
    }

    public function hasFreshFileForCurrentRenewal(): bool
    {
        $file = $this->currentFile;
        if (! $file || ! $file->created_at || ! $this->renew_on) {
            return false;
        }

        return $file->created_at->gte($this->renew_on->copy()->startOfDay());
    }

    /**
     * Recordatorios mientras esté vencido y sin archivo renovado.
     * Intervalo configurable por carpeta (reminder_every_minutes).
     * Si el intervalo es ≥ 60 min, entre 16:00 y 17:00 se acelera a cada 10 minutos.
     */
    public function needsReminder(): bool
    {
        if (! $this->isDueForRenewal()) {
            return false;
        }

        $folder = $this->folder;
        if (! $folder || $folder->exclude_reminders) {
            return false;
        }

        if ($this->hasFreshFileForCurrentRenewal()) {
            return false;
        }

        if ($this->last_reminder_at === null) {
            return true;
        }

        $now = now();
        $interval = $folder->reminderIntervalMinutes();

        // Ventana urgente solo cuando el intervalo normal es horario o mayor.
        if ($interval >= 60 && $now->hour === 16) {
            $interval = 10;
        }

        return $this->last_reminder_at->lte($now->copy()->subMinutes($interval));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
