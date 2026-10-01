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
    private const VENTANA_SEGUNDOS = 12;

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

        // Una sola operación de caché: bloquea durante el procesamiento y un
        // breve enfriamiento; si la operación falla, se libera para reintentar.
        if (!Cache::add($key, 1, self::VENTANA_SEGUNDOS)) {
            return $this->duplicado($request);
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            Cache::forget($key);
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
