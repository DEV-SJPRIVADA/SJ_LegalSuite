<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocumentFolderResponsible extends Model
{
    protected $table = 'legal_document_folder_responsibles';

    protected $fillable = [
        'folder_id',
        'email',
        'name',
        'user_id',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentFolder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function displayName(): string
    {
        $name = trim((string) ($this->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return (string) $this->email;
    }
}
