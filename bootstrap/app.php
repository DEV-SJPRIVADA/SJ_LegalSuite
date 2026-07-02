<?php

use App\Http\Middleware\EnsureMustChangePassword;
use App\Http\Middleware\ForceRequestRootUrl;
use App\Http\Middleware\ShareUiTheme;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('licitaciones:reset-fixed-solicitudes')->hourly();

        if (config('services.pdf.use_queue')) {
            $schedule->command('queue:work database --stop-when-empty --max-time=55')
                ->everyMinute()
                ->withoutOverlapping();
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'must-change-password' => EnsureMustChangePassword::class,
        ]);

        $middleware->web(prepend: [
            ForceRequestRootUrl::class,
        ]);

        $middleware->web(append: [
            ShareUiTheme::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'deploy/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
