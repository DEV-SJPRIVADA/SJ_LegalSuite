<?php

namespace App\Models\Disciplinary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DisciplinaryAgendaAttachment extends Model
{
    protected $fillable = [
        'agenda_message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryAgendaMessage::class, 'agenda_message_id');
    }

    public function deleteStoredFile(): void
    {
        if ($this->path !== '') {
            Storage::disk($this->disk)->delete($this->path);
        }
    }

    /**
     * MIME usable para servir o mostrar la imagen (algunos clientes guardan octet-stream o dejan mime vacío).
     */
    public function inferredImageMimeType(): ?string
    {
        if ($this->mime_type !== null && str_starts_with((string) $this->mime_type, 'image/')) {
            return (string) $this->mime_type;
        }

        $ext = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };
    }

    public function isImage(): bool
    {
        return $this->inferredImageMimeType() !== null;
    }
}
