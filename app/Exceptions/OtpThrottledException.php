<?php

namespace App\Exceptions;

class OtpThrottledException extends OtpException
{
    public function __construct(int $retryAfter, string $message = 'Please wait before requesting another code.')
    {
        parent::__construct($message, ['retry_after' => max(1, $retryAfter)]);
    }

    public function statusCode(): int
    {
        return 429;
    }

    public function errorCode(): string
    {
        return 'otp_throttled';
    }

    public function headers(): array
    {
        return ['Retry-After' => (string) $this->context['retry_after']];
    }
}
