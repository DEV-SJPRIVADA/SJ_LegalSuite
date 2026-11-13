<?php

namespace App\Providers;

use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use App\Policies\LegalDocumentFolderPolicy;
use App\Policies\LicitacionPolicy;
use App\Policies\LicitacionSolicitudPolicy;
use App\Policies\DisciplinaryCasePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InformeSubmissionPolicy;
use App\Policies\UserPolicy;
use App\Support\Disciplinary\DisciplinaryAssets;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Licitacion::class => LicitacionPolicy::class,
        LicitacionSolicitud::class => LicitacionSolicitudPolicy::class,
        LegalDocumentFolder::class => LegalDocumentFolderPolicy::class,
        DisciplinaryCase::class => DisciplinaryCasePolicy::class,
        InformeSubmission::class => InformeSubmissionPolicy::class,
        Employee::class => EmployeePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        // Hostinger: el document root puede ser public_html (sin symlink a public/).
        // Laragon local sigue usando public/ por defecto.
        $hostingerWebRoot = base_path('public_html');

        if ($this->looksLikePublicRoot($hostingerWebRoot)) {
            $this->app->usePublicPath($hostingerWebRoot);
        }
    }

    /**
     * ¿La carpeta realmente sirve como raíz pública?
     *
     * No basta con que exista: en despliegues por Git es frecuente que
     * public_html sea un clon completo del repositorio dentro de la raíz del
     * proyecto. En ese caso tiene artisan/, vendor/ y app/, pero no index.php
     * ni build/manifest.json, así que usarla como public_path rompe @vite
     * (manifest ausente) y los helpers asset()/public_path().
     */
    private function looksLikePublicRoot(string $dir): bool
    {
        if (! is_dir($dir) || ! is_file($dir.DIRECTORY_SEPARATOR.'index.php')) {
            return false;
        }

        // Un clon del repositorio se delata por estos archivos junto al index.php.
        return ! is_file($dir.DIRECTORY_SEPARATOR.'artisan')
            && ! is_dir($dir.DIRECTORY_SEPARATOR.'vendor');
    }

    public function boot(): void
    {
        /*
         * Liberar la conexión HTTP antes de trabajos afterResponse (SMTP, etc.).
         * Sin esto, el navegador del portal de aportación espera a que Office365 termine.
         * Debe registrarse temprano: los callbacks terminating se ejecutan en orden FIFO.
         */
        $this->app->terminating(function () {
            if ($this->app->runningUnitTests()) {
                return;
            }

            if (\function_exists('fastcgi_finish_request')) {
                @\fastcgi_finish_request();
            } elseif (\function_exists('litespeed_finish_request')) {
                @\litespeed_finish_request();
            }

            if (\function_exists('session_write_close')) {
                @\session_write_close();
            }

            while (\ob_get_level() > 0) {
                @\ob_end_flush();
            }
            @\flush();
        });

        /*
         * Laravel @vite inserta <link rel="preload" as="style"> además del stylesheet.
         * Con Livewire (wire:navigate) Chrome suele advertir «preloaded but not used» en bucle.
         * El stylesheet normal basta; el preload de CSS no aporta y ensucia la consola.
         */
        Vite::usePreloadTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if (preg_match('/\.(css|less|sass|scss|pcss|postcss)(\?[^.]*)?$/i', $url) === 1) {
                return false;
            }

            return [];
        });

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Incrusta el logo corporativo en todos los correos (CID) para que no salga roto en Outlook.
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $path = DisciplinaryAssets::logoAbsolutePath();
            if (! is_file($path)) {
                return;
            }

            foreach ($event->message->getAttachments() as $attachment) {
                $contentId = $attachment->getHeaders()->get('Content-ID');
                if ($contentId && str_contains($contentId->getBodyAsString(), 'sj-logo')) {
                    return;
                }
            }

            $event->message->embedFromPath($path, 'sj-logo', 'image/png');
        });
    }
}
