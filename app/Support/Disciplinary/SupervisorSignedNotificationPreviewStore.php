<?php

namespace App\Support\Disciplinary;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SupervisorSignedNotificationPreviewStore
{
    private const CACHE_PREFIX = 'disciplinary_signed_notification_preview:';

    /**
     * @return array{token: string, path: string}
     */
    public function store(
        int $userId,
        string $context,
        int $caseId,
        string $filename,
        string $evidenceType,
        string $binary,
    ): array {
        $token = (string) Str::uuid();
        $path = "disciplinary/signed-preview/{$userId}/{$token}.pdf";

        Storage::disk('local')->put($path, $binary);

        Cache::put($this->cacheKey($token), [
            'user_id' => $userId,
            'context' => $context,
            'case_id' => $caseId,
            'filename' => $filename,
            'evidence_type' => $evidenceType,
            'path' => $path,
        ], now()->addHours(2));

        return ['token' => $token, 'path' => $path];
    }

    /** @return array{user_id: int, context: string, case_id: int, filename: string, evidence_type: string, path: string}|null */
    public function resolve(string $token, int $userId): ?array
    {
        $meta = Cache::get($this->cacheKey($token));

        if (! is_array($meta) || (int) ($meta['user_id'] ?? 0) !== $userId) {
            return null;
        }

        if (! Storage::disk('local')->exists((string) ($meta['path'] ?? ''))) {
            $this->forget($token);

            return null;
        }

        return $meta;
    }

    public function forget(?string $token): void
    {
        if ($token === null || $token === '') {
            return;
        }

        $meta = Cache::get($this->cacheKey($token));
        if (is_array($meta) && isset($meta['path'])) {
            Storage::disk('local')->delete((string) $meta['path']);
        }

        Cache::forget($this->cacheKey($token));
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX.$token;
    }
}
