<?php

namespace App\Services\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrganizationLetterheadService
{
    private const DISK = 'local';

    private const DIR = 'disciplinary/letterhead';

    private const BASENAME = 'official';

    private const META_PATH = 'disciplinary/letterhead/meta.json';

    /** Extensiones admitidas para membrete del acta de comité (HTML → PDF). */
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg'];

    public function hasImage(): bool
    {
        return $this->resolvedRelativePath() !== null;
    }

    public function storeImage(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'letterheadFile' => 'No se pudo leer el archivo de membrete.',
            ]);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'letterheadFile' => 'Solo se admiten imágenes PNG o JPEG para el membrete.',
            ]);
        }

        $mime = (string) ($file->getMimeType() ?? '');
        if (! str_starts_with($mime, 'image/')) {
            throw ValidationException::withMessages([
                'letterheadFile' => 'El archivo debe ser una imagen PNG o JPEG.',
            ]);
        }

        foreach (self::ALLOWED_EXTENSIONS as $oldExt) {
            $old = self::DIR.'/'.self::BASENAME.'.'.$oldExt;
            if (Storage::disk(self::DISK)->exists($old)) {
                Storage::disk(self::DISK)->delete($old);
            }
        }

        $storedName = self::BASENAME.'.'.$ext;
        Storage::disk(self::DISK)->putFileAs(self::DIR, $file, $storedName);

        Storage::disk(self::DISK)->put(self::META_PATH, json_encode([
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime !== '' ? $mime : 'image/png',
            'stored_as' => $storedName,
            'uploaded_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function removeImage(): void
    {
        $relative = $this->resolvedRelativePath();
        if ($relative !== null) {
            Storage::disk(self::DISK)->delete($relative);
        }

        if (Storage::disk(self::DISK)->exists(self::META_PATH)) {
            Storage::disk(self::DISK)->delete(self::META_PATH);
        }
    }

    public function imageDataUri(): ?string
    {
        $relative = $this->resolvedRelativePath();
        if ($relative === null) {
            return null;
        }

        $full = Storage::disk(self::DISK)->path($relative);
        if (! is_file($full)) {
            return null;
        }

        $mime = $this->imageMime() ?? @mime_content_type($full) ?: 'image/png';

        return sprintf('data:%s;base64,%s', $mime, base64_encode((string) file_get_contents($full)));
    }

    public function imageMime(): ?string
    {
        $meta = $this->meta();

        return isset($meta['mime']) ? (string) $meta['mime'] : null;
    }

    public function originalFileName(): ?string
    {
        $meta = $this->meta();

        return isset($meta['original_name']) ? (string) $meta['original_name'] : null;
    }

    public function uploadedAtLabel(): ?string
    {
        $meta = $this->meta();
        $raw = $meta['uploaded_at'] ?? null;
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)
                ->timezone('America/Bogota')
                ->format('d/m/Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Ruta absoluta en disco local (p. ej. para servir inline). */
    public function imageAbsolutePath(): ?string
    {
        $relative = $this->resolvedRelativePath();
        if ($relative === null) {
            return null;
        }

        $full = Storage::disk(self::DISK)->path($relative);

        return is_file($full) ? $full : null;
    }

    /** @return array<string, mixed> */
    private function meta(): array
    {
        if (! Storage::disk(self::DISK)->exists(self::META_PATH)) {
            return [];
        }

        $decoded = json_decode((string) Storage::disk(self::DISK)->get(self::META_PATH), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolvedRelativePath(): ?string
    {
        $meta = $this->meta();
        $storedAs = isset($meta['stored_as']) ? (string) $meta['stored_as'] : '';
        if ($storedAs !== '') {
            $candidate = self::DIR.'/'.$storedAs;
            if (Storage::disk(self::DISK)->exists($candidate)) {
                return $candidate;
            }
        }

        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            $candidate = self::DIR.'/'.self::BASENAME.'.'.$ext;
            if (Storage::disk(self::DISK)->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
