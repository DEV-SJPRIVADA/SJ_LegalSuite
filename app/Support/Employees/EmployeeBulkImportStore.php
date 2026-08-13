<?php

namespace App\Support\Employees;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class EmployeeBulkImportStore
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public static function createFromUploadedFile(int $userId, string $sourcePath): string
    {
        $token = bin2hex(random_bytes(16));
        $dir = self::directoryFor($token);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar la cola de importación de empleados.');
        }

        if (! copy($sourcePath, self::spreadsheetPath($token))) {
            throw new RuntimeException('No se pudo guardar el archivo de importación.');
        }

        self::writeMeta($token, [
            'token' => $token,
            'user_id' => $userId,
            'status' => self::STATUS_PENDING,
            'total_rows' => 0,
            'next_row' => 2,
            'highest_row' => 0,
            'highest_col' => 0,
            'processed_rows' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors_count' => 0,
            'started_at' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'error' => null,
        ]);

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
     * @return array<string, int>
     */
    public static function columnMap(string $token): array
    {
        $path = self::directoryFor($token).'/column_map.json';
        if (! is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, int>  $map
     */
    public static function saveColumnMap(string $token, array $map): void
    {
        file_put_contents(
            self::directoryFor($token).'/column_map.json',
            json_encode($map, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    public static function updateMeta(string $token, array $patch): void
    {
        $meta = self::meta($token);
        if ($meta === null) {
            throw new RuntimeException('Sesión de importación no encontrada.');
        }

        $meta = array_merge($meta, $patch, ['updated_at' => now()->toIso8601String()]);
        self::writeMeta($token, $meta);
    }

    public static function markFailed(string $token, string $message): void
    {
        self::updateMeta($token, [
            'status' => self::STATUS_FAILED,
            'error' => $message,
        ]);
    }

    public static function spreadsheetPath(string $token): string
    {
        return self::directoryFor($token).'/source.xlsx';
    }

    public static function belongsToUser(string $token, int $userId): bool
    {
        $meta = self::meta($token);

        return $meta !== null && (int) ($meta['user_id'] ?? 0) === $userId;
    }

    public static function appendError(string $token, int $row, string $message): void
    {
        $errors = self::errors($token);
        $errors[$row] = $message;

        file_put_contents(
            self::directoryFor($token).'/errors.json',
            json_encode($errors, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function errors(string $token): array
    {
        $path = self::directoryFor($token).'/errors.json';
        if (! is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function delete(string $token): void
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return;
        }

        Cache::lock(self::advanceLockKey($token))->forceRelease();

        $dir = storage_path('app/employee-import-queue/'.$token);
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($dir);
    }

    public static function acquireAdvanceLock(string $token): ?Lock
    {
        $lock = Cache::lock(self::advanceLockKey($token), 120);

        return $lock->get() ? $lock : null;
    }

    private static function advanceLockKey(string $token): string
    {
        return 'employee-import-advance:'.$token;
    }

    public static function directoryFor(string $token): string
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new RuntimeException('Token de importación inválido.');
        }

        return storage_path('app/employee-import-queue/'.$token);
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
