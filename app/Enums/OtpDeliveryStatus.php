<?php

namespace App\Enums;

enum OtpDeliveryStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
