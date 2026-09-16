<?php

namespace App\Support;

/**
 * Despacha jobs de envío sin bloquear la respuesta HTTP.
 *
 * Por defecto usa `dispatchAfterResponse`, que ejecuta el job después de enviar
 * la respuesta (no requiere un worker de cola). Cuando exista un worker en
 * producción, poner QUEUE_AFTER_RESPONSE=false para usar la cola real.
 */
class JobDispatch
{
    /**
     * @param class-string $jobClass
     * @param array<int, mixed> $arguments
     */
    public static function send(string $jobClass, array $arguments = []): void
    {
        if (config('queue.after_response', true)) {
            $jobClass::dispatchAfterResponse(...$arguments);
            return;
        }

        $jobClass::dispatch(...$arguments);
    }
}
