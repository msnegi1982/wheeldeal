<?php

namespace App\Models;

use App\Enums\OtpChannel;
use App\Enums\OtpDeliveryStatus;
use Illuminate\Database\Eloquent\Model;

class OtpDeliveryLog extends Model
{
    protected $fillable = [
        'otp_verification_id',
        'provider',
        'channel',
        'status',
        'provider_message_id',
        'error',
        'payload',
    ];

    protected $casts = [
        'channel' => OtpChannel::class,
        'status' => OtpDeliveryStatus::class,
        'payload' => 'array',
    ];

    public function otpVerification()
    {
        return $this->belongsTo(OtpVerification::class);
    }
}
