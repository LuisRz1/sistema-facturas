<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Evita creaciones repetidas por doble clic o reintentos: si llega dos veces
 * el mismo envío (mismo usuario/IP, ruta y datos) dentro de una ventana corta,
 * rechaza el segundo. Si el cliente envía `submission_id`, la ventana es larga
 * y el bloqueo se libera cuando la operación falla.
 */
class PreventDuplicateSubmission
{
    private const VENTANA_SEGUNDOS = 5;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $submissionId = $request->input('submission_id') ?: $request->header('X-Submission-Id');

        if ($submissionId) {
            $key = 'subm:id:' . md5($request->ip() . '|' . $submissionId);
        } else {
            $payload = $request->except(['_token', '_method', 'submission_id']);
            $key = 'subm:fp:' . md5($request->ip() . '|' . $request->method() . '|' . $request->path() . '|' . http_build_query($payload));
        }

        // Bloqueo durante todo el procesamiento (máx. 60 s) para que un envío
        // lento no deje pasar un segundo; el candado se libera al terminar.
        if (!Cache::add($key, 'processing', 60)) {
            return $this->duplicado($request);
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            // La operación no se completó: permite reintentar de inmediato.
            Cache::forget($key);
        } else {
            // Enfriamiento corto para absorber doble clic/reenvío inmediato.
            Cache::put($key, 'done', self::VENTANA_SEGUNDOS);
        }

        return $response;
    }

    private function duplicado(Request $request): Response
    {
        $mensaje = 'La operación ya se está procesando o ya fue registrada. Espera unos segundos.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $mensaje], 409);
        }

        return back()->with('error', $mensaje);
    }
}
