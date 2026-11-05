<?php

namespace App\Exceptions;

class OtpAttemptsExceededException extends OtpException
{
    public function __construct(string $message = 'Too many incorrect attempts. Request a new code.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'otp_attempts_exceeded';
    }
}
