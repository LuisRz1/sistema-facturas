<?php

namespace Tests\Feature;

use App\Jobs\EnviarCorreo;
use App\Jobs\EnviarWhatsApp;
use App\Jobs\SubirYEnviarPdfWhatsApp;
use App\Services\CloudinaryService;
use App\Services\EmailDeliveryService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnvioJobsTest extends TestCase
{
    public function test_envia_correo_por_gmail_api(): void
    {
        Cache::forget('gmail_api_access_token');

        config([
            'mail.default' => 'gmail-api',
            'mail.from.address' => 'sender@example.com',
            'mail.from.name' => 'Sistema Facturacion',
            'services.gmail.client_id' => 'client-id',
            'services.gmail.client_secret' => 'client-secret',
            'services.gmail.refresh_token' => 'refresh-token',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response(['id' => 'msg']),
        ]);

        (new EnviarCorreo('dest@example.com', 'Asunto', '<b>Hola</b>'))
            ->handle(app(EmailDeliveryService::class));

        Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'gmail.googleapis.com'));
    }

    public function test_envia_whatsapp_texto(): void
    {
        config(['services.whatsapp.gateway_url' => 'https://worker.test']);

        Http::fake(['https://worker.test/send-message' => Http::response(['ok' => true, 'id' => '1'])]);

        (new EnviarWhatsApp('987654321', 'Hola'))
            ->handle(app(WhatsAppGatewayService::class));

        Http::assertSent(fn (Request $r): bool => $r->url() === 'https://worker.test/send-message'
            && $r['phone'] === '51987654321'
            && $r['message'] === 'Hola');
    }

    public function test_sube_pdf_a_cloudinary_y_lo_envia(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo',
            'services.cloudinary.upload_preset' => 'preset',
            'services.cloudinary.timeout' => 5,
            'services.whatsapp.gateway_url' => 'https://worker.test',
        ]);

        Http::fake([
            'https://api.cloudinary.com/*' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/demo/raw/upload/v1/reportes/a.pdf',
            ]),
            'https://worker.test/send-message' => Http::response(['ok' => true]),
        ]);

        (new SubirYEnviarPdfWhatsApp('987654321', base64_encode('%PDF-1.4'), 'a', 'reportes', 'a.pdf', 'cap'))
            ->handle(app(CloudinaryService::class), app(WhatsAppGatewayService::class));

        Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'api.cloudinary.com'));
        Http::assertSent(fn (Request $r): bool => $r->url() === 'https://worker.test/send-message'
            && $r['documentUrl'] === 'https://res.cloudinary.com/demo/raw/upload/fl_attachment/v1/reportes/a.pdf');
    }
}
