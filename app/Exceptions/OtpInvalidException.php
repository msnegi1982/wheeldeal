<?php

namespace App\Exceptions;

class OtpInvalidException extends OtpException
{
    public function __construct(int $attemptsRemaining)
    {
        parent::__construct(
            $attemptsRemaining > 0
                ? 'That code is incorrect. '.$attemptsRemaining.' attempt(s) remaining.'
                : 'That code is incorrect and no attempts remain. Request a new code.',
            ['attempts_remaining' => max(0, $attemptsRemaining)]
        );
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'otp_invalid';
    }
}
