<?php

namespace App\Services\Otp\Drivers;

use App\Enums\OtpChannel;
use App\Exceptions\OtpDeliveryException;
use App\Services\Otp\Contracts\OtpDriverInterface;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;

class TwilioOtpDriver implements OtpDriverInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config = []) {}

    public function provider(): string
    {
        return 'twilio';
    }

    public function send(string $phone, string $code, OtpChannel $channel, array $context = []): ?string
    {
        $sid = $this->config['sid'] ?? null;
        $token = $this->config['token'] ?? null;
        $from = $this->config['from'] ?? null;

        if (! $sid || ! $token || ! $from) {
            throw new OtpDeliveryException('Twilio credentials are not configured.');
        }

        $brand = config('marketplace.brand.name', config('app.name'));
        $expiry = (int) config('otp.expiry_minutes', 5);

        $response = Http::timeout(10)
            ->asForm()
            ->withBasicAuth($sid, $token)
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json', [
                'To' => PhoneNumber::e164($phone),
                'From' => $from,
                'Body' => sprintf('%s verification code: %s. It expires in %d minutes. Do not share it.', $brand, $code, $expiry),
            ]);

        if ($response->failed()) {
            throw new OtpDeliveryException('Twilio rejected the OTP request (HTTP '.$response->status().').');
        }

        return $response->json('sid');
    }
}
