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
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Cola PDF solo con Browsershot + PDF_USE_QUEUE (Dompdf no la necesita).
        if (config('services.pdf.use_queue')
            && strtolower((string) config('services.pdf.driver', 'browsershot')) === 'browsershot') {
            $schedule->command('disciplinary:process-pdf-queue')
                ->everyMinute()
                ->withoutOverlapping(2)
                ->name('disciplinary-process-pdf-queue');
        }
    })->create();
