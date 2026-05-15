<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

/*
 * Carga temprana de .env cuando Apache/PHP aún no tiene APP_KEY (evita 500 intermitente
 * en sesión/Livewire si el .env no se leyó en el primer bootstrap o estaba bloqueado al guardar).
 */
$appBasePath = dirname(__DIR__);
if (! getenv('APP_KEY') && is_readable($appBasePath.'/.env')) {
    Dotenv\Dotenv::createMutable($appBasePath, '.env')->safeLoad();
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
