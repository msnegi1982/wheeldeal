<?php

namespace App\Exceptions;

class OtpAlreadyVerifiedException extends OtpException
{
    public function __construct(string $message = 'This mobile number is already verified.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'otp_already_verified';
    }
}
