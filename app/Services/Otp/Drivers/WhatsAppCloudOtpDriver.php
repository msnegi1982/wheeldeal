<?php

namespace App\Services\Otp\Drivers;

use App\Enums\OtpChannel;
use App\Exceptions\OtpDeliveryException;
use App\Services\Otp\Contracts\OtpDriverInterface;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;

class WhatsAppCloudOtpDriver implements OtpDriverInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config = []) {}

    public function provider(): string
    {
        return 'whatsapp_cloud';
    }

    public function send(string $phone, string $code, OtpChannel $channel, array $context = []): ?string
    {
        $token = $this->config['token'] ?? null;
        $phoneNumberId = $this->config['phone_number_id'] ?? null;
        $template = $this->config['template_name'] ?? null;

        if (! $token || ! $phoneNumberId || ! $template) {
            throw new OtpDeliveryException('WhatsApp Cloud API credentials are not configured.');
        }

        $url = rtrim((string) ($this->config['base_url'] ?? 'https://graph.facebook.com/v19.0'), '/')
            .'/'.$phoneNumberId.'/messages';

        $response = Http::timeout(10)
            ->acceptJson()
            ->withToken($token)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => PhoneNumber::normalize($phone),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new OtpDeliveryException('WhatsApp Cloud API rejected the OTP request (HTTP '.$response->status().').');
        }

        return $response->json('messages.0.id');
    }
}
