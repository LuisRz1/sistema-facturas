<?php

namespace App\Jobs;

use App\Models\NotificacionFactura;
use App\Services\EmailDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarCorreo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $to,
        public string $subject,
        public string $html,
        public ?int $notificationId = null,
    ) {
    }

    public function handle(EmailDeliveryService $emailDelivery): void
    {
        $ok = true;
        $error = null;

        try {
            $emailDelivery->sendHtml($this->to, $this->subject, $this->html);
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
            Log::error('EnviarCorreo: fallo al enviar', ['to' => $this->to, 'error' => $error]);
        }

        if (!$this->notificationId) {
            return;
        }

        $notif = NotificacionFactura::find($this->notificationId);
        if (!$notif) {
            return;
        }

        $notif->update([
            'estado_envio'        => $ok ? 'ENVIADO' : 'ERROR',
            'fecha_envio'         => $ok ? now() : null,
            'respuesta_proveedor' => $ok ? 'Correo enviado correctamente' : $error,
            'observacion'         => $ok ? 'Envío por cola' : 'Error al enviar correo',
            'fecha_actualizacion' => now(),
        ]);
    }
}
