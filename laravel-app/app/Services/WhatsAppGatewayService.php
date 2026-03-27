<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppGatewayService
{
    private string $gatewayUrl = 'https://whastapp-production.up.railway.app/send-message';

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
     * Envía un documento PDF (u otro archivo) desde una URL pública de Cloudinary.
     *
     * El gateway Node.js (whatsapp-web.js) debe manejar este payload así:
     *
     *   const { phone, documentUrl, fileName, message } = body;
     *   const media = await MessageMedia.fromUrl(documentUrl, { unsafeMime: true });
     *   await client.sendMessage(phone + '@c.us', media, {
     *       sendMediaAsDocument: true,
     *       caption: message || '',
     *   });
     *
     * @param string $telefono    Número en formato peruano (9 dígitos) o internacional
     * @param string $documentUrl URL pública del PDF (Cloudinary con fl_attachment)
     * @param string $fileName    Nombre del archivo que verá el receptor (ej: Reporte_CRC_20260315.pdf)
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

        \Log::info('WhatsApp enviarDocumento', [
            'phone'       => $telefono,
            'documentUrl' => $documentUrl,
            'fileName'    => $fileName,
        ]);

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
        try {
            $response = Http::timeout(60)->post($this->gatewayUrl, $payload);

            if ($response->successful()) {
                return ['ok' => true, 'data' => $response->json()];
            }

            return ['ok' => false, 'error' => $response->body()];

        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
