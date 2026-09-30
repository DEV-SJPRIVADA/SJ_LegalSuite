<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalDocumentFolder extends Model
{
    protected $table = 'legal_document_folders';

    /** @var array<int, string> */
    public const REMINDER_INTERVAL_OPTIONS = [
        1 => 'Cada 1 minuto (pruebas)',
        5 => 'Cada 5 minutos',
        10 => 'Cada 10 minutos',
        30 => 'Cada 30 minutos',
        60 => 'Cada 1 hora',
    ];

    protected $fillable = [
        'slug',
        'name',
        'exclude_reminders',
        'reminder_every_minutes',
        'responsible_user_id',
        'responsible_email',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'exclude_reminders' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'reminder_every_minutes' => 'integer',
        ];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LegalDocumentItem::class, 'folder_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LegalDocumentActivity::class, 'folder_id');
    }

    public function reminderIntervalMinutes(): int
    {
        $minutes = (int) ($this->reminder_every_minutes ?: 60);

        return max(1, min(1440, $minutes));
    }

    public function reminderIntervalLabel(): string
    {
        $minutes = $this->reminderIntervalMinutes();

        return self::REMINDER_INTERVAL_OPTIONS[$minutes]
            ?? ('Cada '.$minutes.' minutos');
    }

    public function reminderRecipients(): array
    {
        $emails = [];
        if ($this->responsible_email && filter_var($this->responsible_email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($this->responsible_email);
        }
        if ($this->responsible?->email && filter_var($this->responsible->email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($this->responsible->email);
        }

        return array_values(array_unique($emails));
    }

    public function isAssignedTo(User $user): bool
    {
        if ((int) $this->responsible_user_id === (int) $user->id) {
            return true;
        }

        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return false;
        }

        return strtolower(trim((string) $this->responsible_email)) === $email;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $email = strtolower(trim((string) $user->email));

        return $query->where(function (Builder $inner) use ($user, $email) {
            $inner->where('responsible_user_id', $user->id);
            if ($email !== '') {
                $inner->orWhereRaw('LOWER(responsible_email) = ?', [$email]);
            }
        });
    }
}
