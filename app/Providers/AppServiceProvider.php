<?php

namespace App\Providers;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use App\Policies\DisciplinaryCasePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InformeSubmissionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        DisciplinaryCase::class => DisciplinaryCasePolicy::class,
        InformeSubmission::class => InformeSubmissionPolicy::class,
        Employee::class => EmployeePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        // Hostinger: document root = public_html (no carpeta public ni symlink).
        // Laragon local sigue usando public/ por defecto.
        $hostingerWebRoot = base_path('public_html');
        if (is_dir($hostingerWebRoot)) {
            $this->app->usePublicPath($hostingerWebRoot);
        }
    }

    public function boot(): void
    {
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
    }
}
