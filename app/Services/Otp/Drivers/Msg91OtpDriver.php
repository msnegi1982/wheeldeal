<?php

namespace App\Services\Otp\Drivers;

use App\Enums\OtpChannel;
use App\Exceptions\OtpDeliveryException;
use App\Services\Otp\Contracts\OtpDriverInterface;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;

class Msg91OtpDriver implements OtpDriverInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config = []) {}

    public function provider(): string
    {
        return 'msg91';
    }

    public function send(string $phone, string $code, OtpChannel $channel, array $context = []): ?string
    {
        $authKey = $this->config['auth_key'] ?? null;
        $templateId = $this->config['template_id'] ?? null;

        if (! $authKey || ! $templateId) {
            throw new OtpDeliveryException('MSG91 credentials are not configured.');
        }

        $url = rtrim((string) ($this->config['base_url'] ?? 'https://control.msg91.com/api/v5'), '/').'/otp';

        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders(['authkey' => $authKey])
            ->post($url, [
                'template_id' => $templateId,
                'mobile' => PhoneNumber::normalize($phone),
                'otp' => $code,
                'sender' => $this->config['sender_id'] ?? null,
            ]);

        if ($response->failed()) {
            throw new OtpDeliveryException('MSG91 rejected the OTP request (HTTP '.$response->status().').');
        }

        $payload = $response->json();

        return $payload['request_id']
            ?? $payload['message']
            ?? data_get($payload, 'data.request_id');
    }
}
