<?php

namespace App\Models;

use App\Enums\OtpChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpVerification extends Model
{
    protected $fillable = [
        'uuid',
        'verifiable_type',
        'verifiable_id',
        'phone',
        'channel',
        'purpose',
        'code_hash',
        'attempts',
        'max_attempts',
        'expires_at',
        'last_sent_at',
        'verified_at',
        'ip_address',
    ];

    protected $hidden = ['code_hash'];

    protected $casts = [
        'channel' => OtpChannel::class,
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OtpVerification $otp) {
            $otp->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function verifiable()
    {
        return $this->morphTo();
    }

    public function deliveryLogs()
    {
        return $this->hasMany(OtpDeliveryLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < $this->max_attempts;
    }

    public function canResend(int $cooldownSeconds = 30): bool
    {
        return ! $this->last_sent_at || $this->last_sent_at->addSeconds($cooldownSeconds)->isPast();
    }

    public function secondsUntilResend(int $cooldownSeconds = 30): int
    {
        if (! $this->last_sent_at) {
            return 0;
        }

        return max(0, $cooldownSeconds - (int) $this->last_sent_at->diffInSeconds(now()));
    }
}
