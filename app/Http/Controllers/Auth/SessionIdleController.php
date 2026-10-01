<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SessionIdleController
{
    /**
     * Ping liviano para renovar last_activity_at (middleware).
     */
    public function ping(Request $request): Response
    {
        return response()->noContent();
    }

    /**
     * Cierre explícito por inactividad (avisado en el cliente).
     */
    public function expire(Request $request): RedirectResponse
    {
        $minutes = max(1, (int) config('session.lifetime', 15));

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Su sesión se cerró por inactividad ('.$minutes.' minutos sin uso). Inicie sesión de nuevo.');
    }
}
