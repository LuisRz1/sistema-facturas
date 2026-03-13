<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppGatewayService
{
    private string $gatewayUrl = 'http://localhost:3001/send-message';

    /**
     * Envía un mensaje de WhatsApp.
     * Si se provee $imageUrl (URL pública de Cloudinary), se envía como imagen con caption.
     */
    public function enviar(string $telefono, string $mensaje, ?string $imageUrl = null): array
    {
        $telefono = preg_replace('/\D+/', '', $telefono ?? '');

        if (strlen($telefono) === 9) {
            $telefono = '51' . $telefono;
        }

        $payload = [
            'phone'   => $telefono,
            'message' => $mensaje,
        ];

        if ($imageUrl) {
            $payload['imageUrl'] = $imageUrl;
        }

        try {
            $response = Http::timeout(30)->post($this->gatewayUrl, $payload);

            if ($response->successful()) {
                return ['ok' => true, 'data' => $response->json()];
            }

            return ['ok' => false, 'error' => $response->body()];

        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
