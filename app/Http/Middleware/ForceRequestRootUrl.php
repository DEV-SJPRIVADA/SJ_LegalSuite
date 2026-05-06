<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usa el mismo host/puerto que el navegador (ej. IP LAN cambiante o SJPCANAOPE1:8082)
 * para generar URLs y redirecciones. Actívelo solo en redes de confianza (APP_USE_REQUEST_URL).
 */
class ForceRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.use_request_url', false)) {
            return $next($request);
        }

        $root = $request->getSchemeAndHttpHost();

        if ($root !== '') {
            URL::forceRootUrl($root);
        }

        return $next($request);
    }
}
