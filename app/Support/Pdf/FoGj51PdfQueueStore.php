<?php

namespace App\Support\Pdf;

use Illuminate\Http\UploadedFile;
use RuntimeException;

final class FoGj51PdfQueueStore
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function create(string $intent, int $userId, array $payload): string
    {
        $token = bin2hex(random_bytes(16));
        $dir = self::directoryFor($token);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar la cola de generación PDF.');
        }

        self::writeMeta($token, [
            'token' => $token,
            'intent' => $intent,
            'user_id' => $userId,
            'status' => self::STATUS_PENDING,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'error' => null,
            'redirect_route' => null,
        ]);

        file_put_contents(
            $dir.'/payload.json',
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );

        return $token;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    public static function storeEvidenceFiles(string $token, array $files): array
    {
        $evidenceDir = self::directoryFor($token).'/evidence';
        if (! is_dir($evidenceDir) && ! mkdir($evidenceDir, 0755, true) && ! is_dir($evidenceDir)) {
            throw new RuntimeException('No se pudo guardar las imágenes de evidencia.');
        }

        $stored = [];
        foreach (array_values($files) as $index => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $extension = $file->guessExtension() ?: 'bin';
            $name = 'evidence_'.$index.'.'.$extension;
            $file->move($evidenceDir, $name);
            $stored[] = 'evidence/'.$name;
        }

        return $stored;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function meta(string $token): ?array
    {
        $path = self::directoryFor($token).'/meta.json';
        if (! is_readable($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function payload(string $token): ?array
    {
        $path = self::directoryFor($token).'/payload.json';
        if (! is_readable($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    public static function updateMeta(string $token, array $patch): void
    {
        $meta = self::meta($token);
        if ($meta === null) {
            throw new RuntimeException('Cola PDF no encontrada.');
        }

        $meta = array_merge($meta, $patch, ['updated_at' => now()->toIso8601String()]);
        self::writeMeta($token, $meta);
    }

    public static function outputPath(string $token): string
    {
        return self::directoryFor($token).'/output.pdf';
    }

    public static function directoryFor(string $token): string
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new RuntimeException('Token de cola PDF inválido.');
        }

        return storage_path('app/fo-gj-51-pdf-queue/'.$token);
    }

    public static function belongsToUser(string $token, int $userId): bool
    {
        $meta = self::meta($token);

        return $meta !== null && (int) ($meta['user_id'] ?? 0) === $userId;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private static function writeMeta(string $token, array $meta): void
    {
        file_put_contents(
            self::directoryFor($token).'/meta.json',
            json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }
}
