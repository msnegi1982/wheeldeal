<?php

namespace App\Exceptions;

class OtpExpiredException extends OtpException
{
    public function __construct(string $message = 'This verification code has expired. Request a new one.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'otp_expired';
    }
}
