<?php

namespace App\Http\Controllers\Disciplinary;

use App\Http\Controllers\Controller;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DisciplinaryGeoJsonController extends Controller
{
    private const ALLOWED = [
        'gadm41_COL_1.json',
        'gadm41_COL_2.json',
    ];

    public function __invoke(string $file): BinaryFileResponse|Response
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $model = DisciplinaryCase::class;
        if (! $user->can('viewDashboard', $model) && ! $user->can('viewAny', $model)) {
            abort(403);
        }

        if (! in_array($file, self::ALLOWED, true)) {
            abort(404);
        }

        $path = public_path('geo'.DIRECTORY_SEPARATOR.$file);
        if (! is_readable($path)) {
            abort(404, 'GeoJSON no instalado. Ejecute: php artisan geo:download-colombia-gadm');
        }

        return response()->file($path, [
            'Content-Type' => 'application/geo+json; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
