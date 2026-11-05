<?php

namespace App\Services\Otp\Contracts;

use App\Enums\OtpChannel;

interface OtpDriverInterface
{
    /**
     * Human-readable provider name stored on delivery logs.
     */
    public function provider(): string;

    /**
     * Deliver the plain code. Drivers must never persist or log the code.
     *
     * @param  array<string, mixed>  $context
     * @return string|null Provider message id, when available
     *
     * @throws \App\Exceptions\OtpDeliveryException
     */
    public function send(string $phone, string $code, OtpChannel $channel, array $context = []): ?string;
}
