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

class EnviarWhatsAppDocumento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $telefono,
        public string $documentUrl,
        public string $fileName,
        public string $caption = '',
        public ?int $notificationId = null,
    ) {
    }

    public function handle(WhatsAppGatewayService $gateway): void
    {
        $resultado = $gateway->enviarDocumento($this->telefono, $this->documentUrl, $this->fileName, $this->caption);

        if (!$this->notificationId) {
            return;
        }

        $notif = NotificacionFactura::find($this->notificationId);
        if (!$notif) {
            return;
        }

        if (!$resultado['ok']) {
            Log::warning('EnviarWhatsAppDocumento: fallo al enviar', [
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
            'observacion'         => $resultado['ok'] ? 'Documento enviado' : 'Error al enviar documento',
            'fecha_actualizacion' => now(),
        ]);
    }
}
