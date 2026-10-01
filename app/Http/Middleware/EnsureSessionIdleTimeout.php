<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión tras N minutos sin actividad (SESSION_LIFETIME).
 * También aplica si el usuario marcó «Recordarme».
 */
class EnsureSessionIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $lifetimeMinutes = max(1, (int) config('session.lifetime', 15));
        $idleSeconds = $lifetimeMinutes * 60;
        $now = time();
        $last = (int) $request->session()->get('last_activity_at', 0);

        if ($last > 0 && ($now - $last) > $idleSeconds) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'Su sesión se cerró por inactividad ('.$lifetimeMinutes.' minutos). Inicie sesión de nuevo.');
        }

        $request->session()->put('last_activity_at', $now);

        return $next($request);
    }
}
