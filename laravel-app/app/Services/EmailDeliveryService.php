<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailDeliveryService
{
    public function sendHtml(string $to, string $subject, string $html): void
    {
        if (config('mail.default') !== 'gmail-api') {
            Mail::html($html, fn ($message) => $message
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->to($to)
                ->subject($subject));

            return;
        }

        $this->sendThroughGmailApi($to, $subject, $html);
    }

    private function sendThroughGmailApi(string $to, string $subject, string $html): void
    {
        $clientId = (string) config('services.gmail.client_id');
        $clientSecret = (string) config('services.gmail.client_secret');
        $refreshToken = (string) config('services.gmail.refresh_token');

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('Faltan las credenciales OAuth de Gmail API.');
        }

        $tokenResponse = $this->http()
            ->asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ])
            ->throw();

        $accessToken = (string) $tokenResponse->json('access_token');
        if ($accessToken === '') {
            throw new RuntimeException('Google no devolvió un access token para Gmail API.');
        }

        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to($to)
            ->subject($subject)
            ->html($html);

        $raw = rtrim(strtr(base64_encode($email->toString()), '+/', '-_'), '=');

        $this->http()
            ->withToken($accessToken)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $raw,
            ])
            ->throw();
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout((int) config('services.gmail.timeout', 15));
    }
}
