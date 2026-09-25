<?php

/**
 * Puente de deploy para Hostinger (archivo físico en document root).
 * URL: POST https://dominio/deploy.php?token=...
 * Opcional: &mode=reset  → git fetch + reset --hard origin/main (fuerza sync con GitHub).
 *
 * En Hostinger el document root suele ser public_html/; copiar este archivo allí
 * (o mantenerlo sincronizado vía git si public/ → public_html).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Use POST']);
    exit;
}

$token = (string) ($_GET['token'] ?? '');
$expected = 'Legalsuite.2026'; // cambiar en servidor si se rota el secreto
$mode = strtolower((string) ($_GET['mode'] ?? 'pull'));

if ($token === '' || ! hash_equals($expected, $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Raíz Laravel: un nivel arriba de public/ o public_html/
$root = dirname(__DIR__);
if (! is_dir($root.DIRECTORY_SEPARATOR.'.git') && is_dir(dirname($root).DIRECTORY_SEPARATOR.'.git')) {
    $root = dirname($root);
}

$branch = 'main';

function run_cmd(string $root, string $command): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        $root,
        [
            'GIT_SSH_COMMAND' => 'ssh -i /home/u348559544/.ssh/id_ed25519 -o StrictHostKeyChecking=no',
        ]
    );

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

if ($mode === 'reset') {
    $git = run_cmd($root, 'git fetch origin '.$branch.' && git reset --hard origin/'.$branch);
} else {
    // Evita el error "divergent branches / Need to specify how to reconcile"
    $git = run_cmd($root, 'git pull --no-rebase origin '.$branch);
}

if ($git['code'] !== 0) {
    http_response_code(500);
    echo json_encode([
        'status' => 'git_error',
        'code' => $git['code'],
        'output' => $git['output'],
        'error_detail' => $git['error'],
        'hint' => 'Si hay ramas divergentes, use mode=reset una vez: POST .../deploy.php?token=...&mode=reset',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$artisan = run_cmd($root, 'php artisan optimize:clear');
$migrate = run_cmd($root, 'php artisan migrate --force --no-interaction');

echo json_encode([
    'status' => 'success',
    'mode' => $mode,
    'root' => $root,
    'git' => trim($git['output']."\n".$git['error']),
    'migrate' => trim($migrate['output']."\n".$migrate['error']),
    'artisan' => trim($artisan['output']."\n".$artisan['error']),
    'note' => 'Recuerde subir public/build → public_html/build (Vite no va en Git) y seeders si aplica.',
], JSON_UNESCAPED_UNICODE);
