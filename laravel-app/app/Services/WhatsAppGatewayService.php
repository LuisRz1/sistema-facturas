<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppGatewayService
{
    public function enviar(string $telefono, string $mensaje): array
    {
        $telefono = preg_replace('/\D+/', '', $telefono ?? '');

        if (strlen($telefono) === 9) {
            $telefono = '51' . $telefono;
        }

        $response = Http::post('http://localhost:3001/send-message', [
            'phone' => $telefono,
            'message' => $mensaje,
        ]);

        if ($response->successful()) {
            return [
                'ok' => true,
                'data' => $response->json()
            ];
        }

        return [
            'ok' => false,
            'error' => $response->body()
        ];
    }
}
