<?php

namespace App\Models\LegalDocuments;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LegalDocumentFile extends Model
{
    protected $table = 'legal_document_files';

    protected $fillable = [
        'item_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentItem::class, 'item_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function deleteFromDisk(): void
    {
        if ($this->path !== '' && Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }
}
