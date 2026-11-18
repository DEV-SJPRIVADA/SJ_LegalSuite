<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica peticiones maquina-a-maquina de SJ StatFlow.
 *
 * - X-Integration-Key: token estatico (comparacion tiempo-constante).
 * - X-Signature: HMAC-SHA256 hex del body crudo.
 * - X-Timestamp: unix en segundos, tolerancia +-N segundos.
 */
class VerifyIntegrationSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('integrations.statflow.key');
        $secret = (string) config('integrations.statflow.secret');

        if ($key === '' || $secret === '') {
            return response()->json([
                'ok' => false,
                'message' => 'La integracion no esta configurada en este servidor.',
            ], 503);
        }

        $providedKey = (string) $request->header('X-Integration-Key', '');

        if ($providedKey === '' || ! hash_equals($key, $providedKey)) {
            return response()->json([
                'ok' => false,
                'message' => 'Token de integracion invalido.',
            ], 401);
        }

        $timestamp = (string) $request->header('X-Timestamp', '');

        if (! ctype_digit($timestamp)) {
            return response()->json([
                'ok' => false,
                'message' => 'Falta el header X-Timestamp.',
            ], 422);
        }

        $tolerance = max(1, (int) config('integrations.statflow.timestamp_tolerance', 300));

        if (abs(time() - (int) $timestamp) > $tolerance) {
            return response()->json([
                'ok' => false,
                'message' => 'La peticion esta fuera de la ventana de tiempo permitida.',
            ], 422);
        }

        $signature = strtolower((string) $request->header('X-Signature', ''));
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return response()->json([
                'ok' => false,
                'message' => 'Firma HMAC invalida.',
            ], 422);
        }

        return $next($request);
    }
}
