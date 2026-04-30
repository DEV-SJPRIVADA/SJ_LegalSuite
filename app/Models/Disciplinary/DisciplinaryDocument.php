<?php

namespace App\Models\Disciplinary;

use App\Enums\Disciplinary\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DisciplinaryDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'disciplinary_case_id',
        'disciplinary_stage_id',
        'uploaded_by',
        'document_type',
        'form_code',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'size_bytes' => 'integer',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryStage::class, 'disciplinary_stage_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
