<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegalDocumentFolder extends Model
{
    protected $table = 'legal_document_folders';

    protected $fillable = [
        'slug',
        'name',
        'exclude_reminders',
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
}
