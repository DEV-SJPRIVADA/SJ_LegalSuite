<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocumentActivity extends Model
{
    protected $table = 'legal_document_activities';

    protected $fillable = [
        'folder_id',
        'item_id',
        'user_id',
        'action',
        'summary',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentFolder::class, 'folder_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentItem::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
