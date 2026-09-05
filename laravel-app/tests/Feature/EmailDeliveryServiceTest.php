<?php

namespace Tests\Feature;

use App\Services\EmailDeliveryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailDeliveryServiceTest extends TestCase
{
    public function test_it_sends_html_email_through_gmail_api(): void
    {
        config([
            'mail.default' => 'gmail-api',
            'mail.from.address' => 'sender@example.com',
            'mail.from.name' => 'Sistema Facturacion',
            'services.gmail.client_id' => 'client-id',
            'services.gmail.client_secret' => 'client-secret',
            'services.gmail.refresh_token' => 'refresh-token',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'token_type' => 'Bearer',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'message-id',
            ]),
        ]);

        app(EmailDeliveryService::class)->sendHtml(
            'recipient@example.com',
            'Confirmación de Pago',
            '<strong>Factura pagada</strong>'
        );

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://oauth2.googleapis.com/token') {
                return false;
            }

            return $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['refresh_token'] === 'refresh-token'
                && $request['grant_type'] === 'refresh_token';
        });

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send') {
                return false;
            }

            $raw = (string) $request['raw'];
            $decoded = base64_decode(strtr($raw, '-_', '+/'));

            return $request->hasHeader('Authorization', 'Bearer access-token')
                && str_contains($decoded, 'recipient@example.com')
                && str_contains($decoded, 'Factura pagada');
        });
    }
}
