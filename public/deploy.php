<?php

/**
 * Puente de deploy para Hostinger (archivo físico en el document root).
 *
 * URL: POST https://dominio/deploy.php?token=...
 *   &mode=pull   (por defecto)  → git pull --no-rebase origin main
 *   &mode=reset                 → git fetch + reset --hard origin/main
 *   &mode=check                 → diagnóstico sin modificar nada
 *
 * El token se lee de DEPLOY_WEBHOOK_TOKEN en .env; NO se guarda en el código.
 * Generar uno con:  php -r "echo bin2hex(random_bytes(32));"
 *
 * Requiere que el document root apunte a public/ (donde vive este archivo).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/**
 * Lee una clave del .env sin depender del framework.
 */
function env_value(string $key): ?string
{
    $candidates = [
        dirname(__DIR__).DIRECTORY_SEPARATOR.'.env',
        dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'.env',
    ];

    foreach ($candidates as $path) {
        if (! is_readable($path)) {
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            if (trim($k) !== $key) {
                continue;
            }

            return trim(trim($v), "\"'");
        }
    }

    return null;
}

function run_cmd(string $root, string $command): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, $root);

    if (! is_resource($process)) {
        return ['code' => 1, 'output' => '', 'error' => 'proc_open failed'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);

    return ['code' => $code, 'output' => $stdout, 'error' => $stderr];
}

// ---------------------------------------------------------------------------
// 1. Autenticación (el token nunca se escribe en el código)
// ---------------------------------------------------------------------------
$expected = env_value('DEPLOY_WEBHOOK_TOKEN');
$token = (string) ($_GET['token'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Use POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($expected === null || $expected === '') {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DEPLOY_WEBHOOK_TOKEN no está definido en .env',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($token === '' || ! hash_equals($expected, $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// 2. Raíz del proyecto Laravel (un nivel arriba de public/)
// ---------------------------------------------------------------------------
$root = dirname(__DIR__);
if (! is_dir($root.DIRECTORY_SEPARATOR.'.git') && is_dir(dirname($root).DIRECTORY_SEPARATOR.'.git')) {
    $root = dirname($root);
}

$branch = 'main';
$mode = strtolower((string) ($_GET['mode'] ?? 'pull'));
$php = PHP_BINARY;
$output = [];

// ---------------------------------------------------------------------------
// 3. Modo check: diagnóstico sin tocar el repositorio
// ---------------------------------------------------------------------------
if ($mode === 'check') {
    $envOk = is_readable($root.'/.env');
    $keyOk = (env_value('APP_KEY') ?? '') !== '';
    $manifest = $root.'/public/build/manifest.json';

    echo json_encode([
        'status' => 'check',
        'root' => $root,
        'git_dir' => is_dir($root.'/.git'),
        'env_exists' => $envOk,
        'app_key_set' => $keyOk,
        'build_manifest' => is_file($manifest),
        'build_files' => is_file($manifest)
            ? array_map(
                fn ($e) => $e['file'] ?? '',
                array_filter((array) (json_decode((string) file_get_contents($manifest), true) ?: []), 'is_array')
            )
            : [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ---------------------------------------------------------------------------
// 4. Actualizar código
// ---------------------------------------------------------------------------
if ($mode === 'reset') {
    $git = run_cmd($root, 'git fetch origin '.$branch.' && git reset --hard origin/'.$branch);
} else {
    $git = run_cmd($root, 'git pull --no-rebase origin '.$branch);
}
$output['git'] = trim($git['output']."\n".$git['error']);

if ($git['code'] !== 0) {
    http_response_code(500);
    echo json_encode([
        'status' => 'git_error',
        'code' => $git['code'],
        'output' => $git['output'],
        'error_detail' => $git['error'],
        'hint' => 'Si hay ramas divergentes use mode=reset una vez.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// 5. Post-deploy: dependencias, cachés, migraciones, enlace de storage
//    (los assets Vite vienen compilados en Git: no se ejecuta npm aquí)
// ---------------------------------------------------------------------------
$steps = [
    'composer' => 'composer install --no-dev --prefer-dist --no-interaction --quiet',
    'storage_link' => $php.' artisan storage:link',
    'optimize_clear' => $php.' artisan optimize:clear',
    'migrate' => $php.' artisan migrate --force --no-interaction',
    'config_cache' => $php.' artisan config:cache',
    'route_cache' => $php.' artisan route:cache',
    'view_cache' => $php.' artisan view:cache',
];

$results = [];
foreach ($steps as $name => $command) {
    $r = run_cmd($root, $command);
    $results[$name] = [
        'code' => $r['code'],
        'output' => trim($r['output']."\n".$r['error']),
    ];
}

echo json_encode([
    'status' => 'success',
    'mode' => $mode,
    'root' => $root,
    'git' => $output['git'],
    'steps' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
