<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function responsibles(): HasMany
    {
        return $this->hasMany(LegalDocumentFolderResponsible::class, 'folder_id')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->orderBy('email');
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

    /**
     * @return list<string>
     */
    public function reminderRecipients(): array
    {
        $emails = [];

        foreach ($this->relationLoaded('responsibles') ? $this->responsibles : $this->responsibles()->get() as $row) {
            if ($row->email && filter_var($row->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($row->email);
            }
            if ($row->user?->email && filter_var($row->user->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($row->user->email);
            }
        }

        // Compatibilidad: columnas legacy de director.
        if ($this->responsible_email && filter_var($this->responsible_email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($this->responsible_email);
        }
        if ($this->responsible?->email && filter_var($this->responsible->email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($this->responsible->email);
        }

        return array_values(array_unique($emails));
    }

    public function primaryResponsible(): ?LegalDocumentFolderResponsible
    {
        if ($this->relationLoaded('responsibles')) {
            return $this->responsibles->firstWhere('is_primary', true)
                ?? $this->responsibles->first();
        }

        return $this->responsibles()->where('is_primary', true)->first()
            ?? $this->responsibles()->first();
    }

    /**
     * Etiqueta corta de responsables (director + extras).
     */
    public function responsiblesLabel(int $max = 3): string
    {
        /** @var Collection<int, LegalDocumentFolderResponsible> $rows */
        $rows = $this->relationLoaded('responsibles')
            ? $this->responsibles
            : $this->responsibles()->get();

        if ($rows->isEmpty()) {
            $email = strtolower(trim((string) ($this->responsible_email ?? '')));
            if ($email === '') {
                return 'sin asignar';
            }

            return $email;
        }

        $labels = $rows->take($max)->map(function (LegalDocumentFolderResponsible $row) {
            $label = $row->displayName();

            return $row->is_primary ? $label.' (director)' : $label;
        })->all();

        $extra = $rows->count() - $max;
        if ($extra > 0) {
            $labels[] = '+'.$extra;
        }

        return implode(', ', $labels);
    }

    /**
     * Sincroniza responsables y deja el principal en columnas legacy.
     *
     * @param  list<array{email: string, name?: string, user_id?: int|null, is_primary?: bool}>  $people
     */
    public function syncResponsibles(array $people): void
    {
        $normalized = [];
        foreach ($people as $person) {
            $email = strtolower(trim((string) ($person['email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $normalized[$email] = [
                'email' => $email,
                'name' => trim((string) ($person['name'] ?? '')) ?: null,
                'user_id' => $person['user_id'] ?? null,
                'is_primary' => (bool) ($person['is_primary'] ?? false),
            ];
        }

        $list = array_values($normalized);
        if ($list !== [] && ! collect($list)->contains(fn ($p) => $p['is_primary'])) {
            $list[0]['is_primary'] = true;
        }

        // Solo un principal.
        $primarySeen = false;
        foreach ($list as $i => $person) {
            if ($person['is_primary']) {
                if ($primarySeen) {
                    $list[$i]['is_primary'] = false;
                } else {
                    $primarySeen = true;
                }
            }
        }

        $keepEmails = array_column($list, 'email');
        $this->responsibles()->whereNotIn('email', $keepEmails ?: ['__none__'])->delete();

        foreach ($list as $person) {
            $this->responsibles()->updateOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'user_id' => $person['user_id'],
                    'is_primary' => $person['is_primary'],
                ],
            );
        }

        $primary = collect($list)->firstWhere('is_primary', true);
        $this->update([
            'responsible_email' => $primary['email'] ?? null,
            'responsible_user_id' => $primary['user_id'] ?? null,
        ]);

        $this->unsetRelation('responsibles');
        $this->unsetRelation('responsible');
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

        if (strtolower(trim((string) $this->responsible_email)) === $email) {
            return true;
        }

        return $this->responsibles()
            ->where(function (Builder $q) use ($user, $email) {
                $q->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(email) = ?', [$email]);
            })
            ->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $email = strtolower(trim((string) $user->email));

        return $query->where(function (Builder $inner) use ($user, $email) {
            $inner->where('responsible_user_id', $user->id);
            if ($email !== '') {
                $inner->orWhereRaw('LOWER(responsible_email) = ?', [$email]);
            }
            $inner->orWhereHas('responsibles', function (Builder $rq) use ($user, $email) {
                $rq->where('user_id', $user->id);
                if ($email !== '') {
                    $rq->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            });
        });
    }
}
