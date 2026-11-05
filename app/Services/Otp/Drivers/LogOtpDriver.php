<?php

namespace App\Services\Otp\Drivers;

use App\Enums\OtpChannel;
use App\Services\Otp\Contracts\OtpDriverInterface;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogOtpDriver implements OtpDriverInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config = []) {}

    public function provider(): string
    {
        return 'log';
    }

    public function send(string $phone, string $code, OtpChannel $channel, array $context = []): ?string
    {
        Log::info('OTP dispatch (log driver)', [
            'channel' => $channel->value,
            'phone' => PhoneNumber::masked($phone),
            'purpose' => $context['purpose'] ?? null,
        ]);

        // The code is only written to the log in local/testing so a developer
        // can complete the flow without a real provider. Never in production.
        if (app()->environment(['local', 'testing'])) {
            Log::debug('OTP code (log driver)', [
                'phone' => PhoneNumber::masked($phone),
                'code' => $code,
            ]);
        }

        return 'log-'.Str::uuid();
    }
}
