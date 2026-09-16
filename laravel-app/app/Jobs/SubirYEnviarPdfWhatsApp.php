<?php

namespace App\Jobs;

use App\Services\CloudinaryService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sube un PDF (recibido en base64) a Cloudinary y lo envía por WhatsApp.
 * Las dos llamadas de red lentas quedan fuera de la request HTTP.
 */
class SubirYEnviarPdfWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $telefono,
        public string $pdfBase64,
        public string $publicId,
        public string $folder,
        public string $fileName,
        public string $caption = '',
    ) {
    }

    public function handle(CloudinaryService $cloudinary, WhatsAppGatewayService $gateway): void
    {
        $pdfContent = base64_decode($this->pdfBase64, true);
        if ($pdfContent === false || $pdfContent === '') {
            Log::error('SubirYEnviarPdfWhatsApp: PDF inválido', ['public_id' => $this->publicId]);
            return;
        }

        $url = $cloudinary->subirRaw($pdfContent, $this->publicId, $this->folder);
        if (!$url) {
            Log::error('SubirYEnviarPdfWhatsApp: no se pudo subir a Cloudinary', ['public_id' => $this->publicId]);
            return;
        }

        $gateway->enviarDocumento($this->telefono, $url, $this->fileName, $this->caption);
    }
}
