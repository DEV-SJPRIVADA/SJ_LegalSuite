<?php

namespace App\Support\Pdf;

use RuntimeException;

/**
 * Cola en disco para PDF FO-GJ-03 (preview / generate) en hosting con PDF_USE_QUEUE.
 */
final class FoGj03PdfQueueStore
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function create(string $intent, int $userId, int $caseId, array $payload = []): string
    {
        $token = bin2hex(random_bytes(16));
        $dir = self::directoryFor($token);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar la cola de FO-GJ-03.');
        }

        self::writeMeta($token, [
            'token' => $token,
            'intent' => $intent,
            'user_id' => $userId,
            'case_id' => $caseId,
            'status' => self::STATUS_PENDING,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'error' => null,
            'inline' => (bool) ($payload['inline'] ?? false),
        ]);

        file_put_contents(
            $dir.'/payload.json',
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );

        return $token;
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
     * @param  array<string, mixed>  $patch
     */
    public static function updateMeta(string $token, array $patch): void
    {
        $meta = self::meta($token);
        if ($meta === null) {
            throw new RuntimeException('Cola FO-GJ-03 no encontrada.');
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
            throw new RuntimeException('Token de cola FO-GJ-03 inválido.');
        }

        return storage_path('app/fo-gj-03-pdf-queue/'.$token);
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
