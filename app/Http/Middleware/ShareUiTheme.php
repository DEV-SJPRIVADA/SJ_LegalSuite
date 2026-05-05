<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareUiTheme
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $theme = 'light';

        if ($request->user()) {
            $t = $request->user()->theme ?? 'light';
            $theme = in_array($t, ['light', 'dark'], true) ? $t : 'light';
        }

        view()->share('uiTheme', $theme);

        return $next($request);
    }
}
