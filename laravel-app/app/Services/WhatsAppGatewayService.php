<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    /**
     * URL del endpoint /send-message del worker Node (Baileys).
     * Configurable por env para no acoplar el código a un despliegue concreto.
     */
    private function endpoint(): string
    {
        $base = rtrim((string) config('services.whatsapp.gateway_url', 'https://whastapp-production.up.railway.app'), '/');

        if (str_ends_with($base, '/send-message')) {
            return $base;
        }

        return $base . '/send-message';
    }

    /**
     * Envía un mensaje de texto (con imagen opcional adjunta).
     */
    public function enviar(string $telefono, string $mensaje, ?string $imageUrl = null): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $payload = [
            'phone'   => $telefono,
            'message' => $mensaje,
        ];

        if ($imageUrl) {
            $payload['imageUrl'] = $imageUrl;
        }

        return $this->post($payload);
    }

    /**
     * Envía un documento PDF (u otro archivo) desde una URL pública.
     *
     * @param string $telefono    Número en formato peruano (9 dígitos) o internacional
     * @param string $documentUrl URL pública del PDF (Cloudinary con fl_attachment)
     * @param string $fileName    Nombre del archivo que verá el receptor
     * @param string $caption     Texto que acompaña al documento (opcional)
     */
    public function enviarDocumento(string $telefono, string $documentUrl, string $fileName, string $caption = ''): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $payload = [
            'phone'       => $telefono,
            'documentUrl' => $documentUrl,
            'fileName'    => $fileName,
            'message'     => $caption,
        ];

        return $this->post($payload);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function formatearTelefono(string $telefono): string
    {
        $telefono = preg_replace('/\D+/', '', $telefono ?? '');

        if (strlen($telefono) === 9) {
            $telefono = '51' . $telefono;
        }

        return $telefono;
    }

    private function post(array $payload): array
    {
        $url = $this->endpoint();
        $timeout = (int) config('services.whatsapp.timeout', 30);

        try {
            $response = Http::timeout($timeout)->post($url, $payload);

            if ($response->successful()) {
                return ['ok' => true, 'data' => $response->json()];
            }

            $error = $this->extractError($response->body(), $response->status());

            Log::warning('WhatsApp gateway devolvió error', [
                'status' => $response->status(),
                'error'  => $error,
                'tipo'   => isset($payload['documentUrl']) ? 'documento' : (isset($payload['imageUrl']) ? 'imagen' : 'texto'),
            ]);

            return ['ok' => false, 'error' => $error];

        } catch (\Throwable $e) {
            Log::error('WhatsApp gateway inaccesible', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'No se pudo contactar al servicio de WhatsApp: ' . $e->getMessage()];
        }
    }

    private function extractError(string $body, int $status): string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded) && !empty($decoded['error'])) {
            return (string) $decoded['error'];
        }

        $body = trim($body);

        return $body !== '' ? mb_substr($body, 0, 300) : "HTTP {$status}";
    }
}
