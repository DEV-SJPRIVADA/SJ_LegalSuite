<?php

namespace App\Http\Controllers\Licitaciones;

use App\Http\Controllers\Controller;
use App\Models\Licitaciones\LicitacionAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicitacionAdjuntoInlineController extends Controller
{
    public function __invoke(Request $request, LicitacionAdjunto $adjunto): StreamedResponse
    {
        $adjunto->load(['licitacion', 'solicitud']);

        if ($adjunto->licitacion_id && $adjunto->licitacion) {
            Gate::authorize('view', $adjunto->licitacion);
        } elseif ($adjunto->solicitud_id && $adjunto->solicitud) {
            Gate::authorize('view', $adjunto->solicitud);
        } else {
            abort(404);
        }

        abort_unless(Storage::disk($adjunto->disk)->exists($adjunto->path), 404);

        return Storage::disk($adjunto->disk)->response(
            $adjunto->path,
            $adjunto->nombre_archivo,
            ['Content-Type' => $adjunto->mime_type ?? 'application/octet-stream']
        );
    }
}
