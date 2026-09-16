<?php

namespace App\Jobs;

use App\Models\NotificacionFactura;
use App\Services\WhatsAppGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $telefono,
        public string $mensaje,
        public ?string $imageUrl = null,
        public ?int $notificationId = null,
    ) {
    }

    public function handle(WhatsAppGatewayService $gateway): void
    {
        $resultado = $gateway->enviar($this->telefono, $this->mensaje, $this->imageUrl);

        $this->registrar(
            $resultado,
            $this->imageUrl ? 'Enviado con imagen' : 'Enviado sin imagen'
        );
    }

    private function registrar(array $resultado, string $observacion): void
    {
        if (!$this->notificationId) {
            return;
        }

        $notif = NotificacionFactura::find($this->notificationId);
        if (!$notif) {
            return;
        }

        if (!$resultado['ok']) {
            Log::warning('EnviarWhatsApp: fallo al enviar', [
                'telefono' => $this->telefono,
                'error'    => $resultado['error'] ?? null,
            ]);
        }

        $notif->update([
            'estado_envio'        => $resultado['ok'] ? 'ENVIADO' : 'ERROR',
            'fecha_envio'         => $resultado['ok'] ? now() : null,
            'respuesta_proveedor' => $resultado['ok']
                ? json_encode($resultado['data'] ?? [], JSON_UNESCAPED_UNICODE)
                : ($resultado['error'] ?? null),
            'observacion'         => $observacion,
            'fecha_actualizacion' => now(),
        ]);
    }
}
