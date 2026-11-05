<?php

namespace App\Jobs;

use App\Enums\OtpDeliveryStatus;
use App\Models\OtpDeliveryLog;
use App\Models\OtpVerification;
use App\Services\Otp\OtpManager;
use App\Support\PhoneNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30];

    /**
     * The plain code travels only in the queue payload. It is never written to
     * the database or delivery logs.
     */
    public function __construct(
        public OtpVerification $otp,
        public string $code
    ) {}

    public function handle(OtpManager $manager): void
    {
        $this->otp->refresh();

        if ($this->otp->isVerified() || $this->otp->isExpired()) {
            return;
        }

        $driver = $manager->forChannel($this->otp->channel);

        $log = OtpDeliveryLog::create([
            'otp_verification_id' => $this->otp->id,
            'provider' => $driver->provider(),
            'channel' => $this->otp->channel,
            'status' => OtpDeliveryStatus::QUEUED,
            'payload' => [
                'purpose' => $this->otp->purpose,
                'phone' => PhoneNumber::masked($this->otp->phone),
            ],
        ]);

        try {
            $messageId = $driver->send(
                $this->otp->phone,
                $this->code,
                $this->otp->channel,
                ['purpose' => $this->otp->purpose]
            );

            $log->update([
                'status' => OtpDeliveryStatus::SENT,
                'provider_message_id' => $messageId,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => OtpDeliveryStatus::FAILED,
                'error' => Str::limit($e->getMessage(), 1000),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        OtpDeliveryLog::query()
            ->where('otp_verification_id', $this->otp->id)
            ->where('status', OtpDeliveryStatus::QUEUED->value)
            ->latest('id')
            ->limit(1)
            ->update([
                'status' => OtpDeliveryStatus::FAILED->value,
                'error' => Str::limit($exception->getMessage(), 1000),
            ]);
    }
}
