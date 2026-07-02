<?php

namespace App\Support\Pdf;

final class PdfCliPhpBinaryResolver
{
    public static function resolve(): string
    {
        $configured = config('services.pdf.cli_php_binary');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (PHP_SAPI === 'cli' && defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '' && is_file(PHP_BINARY)) {
            return PHP_BINARY;
        }

        $version = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        foreach ([
            "/opt/alt/php{$version}/usr/bin/php",
            '/opt/alt/php83/usr/bin/php',
            '/opt/alt/php82/usr/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }
}
