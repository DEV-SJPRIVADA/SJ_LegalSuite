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

    /** Nota en documentos de evidencia copiados del FO-GJ-51 al autorizar el expediente. */
    public const NOTE_FO51_AUTHORIZED_EVIDENCE = 'Evidencia fotográfica del informe FO-GJ-51 (conservada al autorizar).';

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

    /**
     * Detecta imágenes típicas de evidencia aunque el MIME guardado sea genérico (p. ej. octet-stream en Windows).
     */
    public function isLikelyRasterImage(): bool
    {
        $mime = strtolower((string) ($this->mime_type ?? ''));
        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $ext = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }
}
