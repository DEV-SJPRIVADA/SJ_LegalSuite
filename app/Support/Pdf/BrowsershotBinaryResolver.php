<?php

namespace App\Support\Pdf;

/**
 * Resuelve ejecutables para Spatie Browsershot cuando .env está vacío (p. ej. Laragon en Windows).
 */
final class BrowsershotBinaryResolver
{
    public static function nodeBinary(): ?string
    {
        $configured = config('services.pdf.node_binary');
        if (self::isExecutableFile($configured)) {
            return $configured;
        }

        return match (PHP_OS_FAMILY) {
            'Windows' => self::laragonNodeExe(),
            default => self::firstExecutableInPath(['node']),
        };
    }

    public static function npmBinary(): ?string
    {
        $configured = config('services.pdf.npm_binary');
        if (self::isExecutableFile($configured)) {
            return $configured;
        }

        $node = self::nodeBinary();
        if ($node === null) {
            return null;
        }

        $dir = dirname($node);

        if (PHP_OS_FAMILY === 'Windows') {
            $npmCmd = $dir.DIRECTORY_SEPARATOR.'npm.cmd';
            if (is_file($npmCmd)) {
                return $npmCmd;
            }
        }

        $npm = $dir.DIRECTORY_SEPARATOR.'npm';
        if (is_file($npm)) {
            return $npm;
        }

        return self::firstExecutableInPath(['npm']);
    }

    /**
     * Chrome/Chromium para imprimir PDF. Si es null, Puppeteer usa su Chromium por defecto (tras npm install).
     */
    public static function chromeBinary(): ?string
    {
        $configured = config('services.pdf.chrome_path');
        if (self::isExecutableFile($configured)) {
            return $configured;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return self::firstExecutableInPath(['google-chrome', 'chromium', 'chromium-browser']);
        }

        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function isExecutableFile(mixed $path): bool
    {
        return is_string($path) && $path !== '' && is_file($path);
    }

    private static function laragonNodeExe(): ?string
    {
        $patterns = [
            'C:\\laragon\\bin\\nodejs\\node-v*\\node.exe',
            'D:\\laragon\\bin\\nodejs\\node-v*\\node.exe',
        ];

        $found = [];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern, GLOB_NOSORT) ?: [] as $path) {
                $found[] = $path;
            }
        }

        if ($found === []) {
            return null;
        }

        sort($found, SORT_NATURAL | SORT_FLAG_CASE);

        return end($found) ?: null;
    }

    /**
     * @param  list<string>  $names
     */
    private static function firstExecutableInPath(array $names): ?string
    {
        $pathEnv = getenv('PATH') ?: '';
        $dirs = explode(PATH_SEPARATOR, $pathEnv);

        foreach ($names as $name) {
            foreach ($dirs as $dir) {
                if ($dir === '') {
                    continue;
                }
                $candidate = $dir.DIRECTORY_SEPARATOR.$name;
                if (PHP_OS_FAMILY === 'Windows' && ! str_ends_with(strtolower($candidate), '.exe')) {
                    $candidate .= '.exe';
                }
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
