<?php

namespace App\Exceptions;

class OtpDeliveryException extends OtpException
{
    public function __construct(string $message = 'We could not send the verification code. Please try again.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'otp_delivery_failed';
    }
}
