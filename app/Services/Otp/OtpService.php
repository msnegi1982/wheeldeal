<?php

namespace App\Services\Otp;

use App\Enums\OtpChannel;
use App\Exceptions\OtpAlreadyVerifiedException;
use App\Exceptions\OtpAttemptsExceededException;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpNotFoundException;
use App\Exceptions\OtpThrottledException;
use App\Jobs\SendOtpJob;
use App\Models\OtpVerification;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private OtpManager $manager) {}

    /**
     * Create and queue a fresh one-time code. Any earlier unverified code for
     * the same phone/purpose is invalidated but retained for audit history.
     */
    public function send(
        string $phone,
        OtpChannel $channel,
        string $purpose = 'vehicle_request',
        ?Model $verifiable = null,
        ?string $ip = null
    ): OtpVerification {
        $phone = $this->normalizePhone($phone);

        $this->enforceRateLimits($phone, $ip, $purpose);

        $code = $this->generateCode();

        $otp = DB::transaction(function () use ($phone, $channel, $purpose, $verifiable, $ip, $code) {
            OtpVerification::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);

            return OtpVerification::create([
                'verifiable_type' => $verifiable?->getMorphClass(),
                'verifiable_id' => $verifiable?->getKey(),
                'phone' => $phone,
                'channel' => $channel,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'max_attempts' => (int) config('otp.max_attempts', 3),
                'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 5)),
                'last_sent_at' => now(),
                'ip_address' => $ip,
            ]);
        });

        SendOtpJob::dispatch($otp, $code)->afterCommit();

        return $otp;
    }

    /**
     * Re-send an existing verification session so the public URL (request UUID)
     * stays stable.
     */
    public function resend(OtpVerification $otp, ?string $ip = null): OtpVerification
    {
        $otp->refresh();

        if ($otp->isVerified()) {
            throw new OtpAlreadyVerifiedException;
        }

        $cooldown = (int) config('otp.resend_cooldown_seconds', 30);

        if (! $otp->canResend($cooldown)) {
            throw new OtpThrottledException(
                $otp->secondsUntilResend($cooldown),
                'Please wait before requesting another code.'
            );
        }

        $this->enforceRateLimits($otp->phone, $ip, $otp->purpose);

        $code = $this->generateCode();

        $otp = DB::transaction(function () use ($otp, $ip, $code) {
            $otp->update([
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 5)),
                'last_sent_at' => now(),
                'ip_address' => $ip ?: $otp->ip_address,
            ]);

            return $otp;
        });

        SendOtpJob::dispatch($otp, $code)->afterCommit();

        return $otp;
    }

    /**
     * @throws OtpNotFoundException
     * @throws OtpExpiredException
     * @throws OtpAttemptsExceededException
     * @throws OtpInvalidException
     * @throws OtpAlreadyVerifiedException
     */
    public function verify(OtpVerification $otp, string $code): OtpVerification
    {
        $otp->refresh();

        if ($otp->isVerified()) {
            throw new OtpAlreadyVerifiedException;
        }

        if ($otp->isExpired()) {
            throw new OtpExpiredException;
        }

        if (! $otp->hasAttemptsLeft()) {
            throw new OtpAttemptsExceededException;
        }

        if (! Hash::check(trim($code), $otp->code_hash)) {
            $otp->increment('attempts');
            $remaining = max(0, $otp->max_attempts - ($otp->attempts + 1));

            if ($remaining === 0) {
                throw new OtpAttemptsExceededException;
            }

            throw new OtpInvalidException($remaining);
        }

        DB::transaction(function () use ($otp) {
            $otp->forceFill(['verified_at' => now()])->save();

            OtpVerification::query()
                ->where('phone', $otp->phone)
                ->where('purpose', $otp->purpose)
                ->whereKeyNot($otp->getKey())
                ->whereNull('verified_at')
                ->update(['expires_at' => now()]);
        });

        return $otp->refresh();
    }

    public function findByUuid(string $uuid): OtpVerification
    {
        $otp = OtpVerification::query()->where('uuid', $uuid)->first();

        if (! $otp) {
            throw new OtpNotFoundException;
        }

        return $otp;
    }

    public function latestFor(Model $verifiable, string $purpose = 'vehicle_request'): ?OtpVerification
    {
        return OtpVerification::query()
            ->where('verifiable_type', $verifiable->getMorphClass())
            ->where('verifiable_id', $verifiable->getKey())
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();
    }

    public function secondsUntilResend(OtpVerification $otp): int
    {
        return $otp->secondsUntilResend((int) config('otp.resend_cooldown_seconds', 30));
    }

    public function attemptsRemaining(OtpVerification $otp): int
    {
        return max(0, (int) $otp->max_attempts - (int) $otp->attempts);
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = PhoneNumber::normalize($phone);

        if (! PhoneNumber::isValidIndianMobile($normalized)) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid 10-digit Indian mobile number.',
            ]);
        }

        return $normalized;
    }

    private function generateCode(): string
    {
        $length = (int) config('otp.length', 6);
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('9', $length, '9');

        return str_pad((string) random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }

    private function enforceRateLimits(string $phone, ?string $ip, string $purpose): void
    {
        $phoneLimit = (int) config('otp.rate_limits.phone_per_hour', 5);
        $ipLimit = (int) config('otp.rate_limits.ip_per_hour', 15);

        $phoneKey = 'otp-phone:'.sha1($phone.'|'.$purpose);

        if (RateLimiter::tooManyAttempts($phoneKey, $phoneLimit)) {
            throw new OtpThrottledException(RateLimiter::availableIn($phoneKey));
        }

        RateLimiter::hit($phoneKey, 3600);

        if ($ip) {
            $ipKey = 'otp-ip:'.sha1($ip);

            if (RateLimiter::tooManyAttempts($ipKey, $ipLimit)) {
                throw new OtpThrottledException(RateLimiter::availableIn($ipKey));
            }

            RateLimiter::hit($ipKey, 3600);
        }
    }
}
