<?php

namespace App\Services\Requests;

use App\Enums\OtpChannel;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Exceptions\OtpAlreadyVerifiedException;
use App\Exceptions\OtpNotFoundException;
use App\Models\CustomerRequest;
use App\Models\OtpVerification;
use App\Models\RequestNote;
use App\Models\User;
use App\Services\Media\VehicleMediaService;
use App\Services\Otp\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerRequestService
{
    public function __construct(
        private OtpService $otp,
        private VehicleMediaService $media,
    ) {}

    /**
     * Persist the sell/buy request first, then queue the OTP. Keeping the
     * pending row makes abandoned/failed verifications auditable.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $photos
     * @return array{request: CustomerRequest, otp: OtpVerification}
     */
    public function submit(
        RequestType $type,
        array $data,
        array $photos = [],
        ?string $ip = null,
        ?string $userAgent = null,
        OtpChannel $channel = OtpChannel::SMS,
        string $source = 'web'
    ): array {
        $phone = PhoneNumber::normalize((string) ($data['phone'] ?? ''));

        if (! PhoneNumber::isValidIndianMobile($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid 10-digit Indian mobile number.',
            ]);
        }

        $request = DB::transaction(function () use ($type, $data, $phone, $ip, $userAgent, $source) {
            return CustomerRequest::create([
                'type' => $type,
                'status' => RequestStatus::PENDING_VERIFICATION,
                'name' => trim((string) $data['name']),
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'vehicle_model_id' => $data['vehicle_model_id'] ?? null,
                'vehicle_variant_id' => $data['vehicle_variant_id'] ?? null,
                'year' => $data['year'] ?? null,
                'km_driven' => $data['km_driven'] ?? null,
                'expected_price' => $data['expected_price'] ?? null,
                'condition' => $data['condition'] ?? null,
                'colour' => $data['colour'] ?? null,
                'message' => $data['message'] ?? null,
                'consent' => (bool) ($data['consent'] ?? false),
                'source' => $source,
                'ip_address' => $ip,
                'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            ]);
        });

        if ($photos !== []) {
            $this->media->addRequestPhotos($request, $photos);
        }

        $otp = $this->otp->send($phone, $channel, 'vehicle_request', $request, $ip);

        $this->log($request, 'request_submitted', [
            'type' => $type->value,
            'channel' => $channel->value,
        ]);

        return ['request' => $request, 'otp' => $otp];
    }

    public function verifyOtp(CustomerRequest $request, string $code): OtpVerification
    {
        if ($request->isVerified()) {
            throw new OtpAlreadyVerifiedException('This request is already verified.');
        }

        $otp = $this->latestOtp($request);

        if (! $otp) {
            throw new OtpNotFoundException('No verification code was found. Please request a new one.');
        }

        $otp = $this->otp->verify($otp, $code);

        DB::transaction(function () use ($request) {
            $request->forceFill([
                'otp_verified_at' => now(),
                'status' => $request->status === RequestStatus::PENDING_VERIFICATION
                    ? RequestStatus::NEW
                    : $request->status,
            ])->save();
        });

        $this->log($request, 'otp_verified');

        return $otp;
    }

    public function resendOtp(CustomerRequest $request, ?string $ip = null): OtpVerification
    {
        if ($request->isVerified()) {
            throw new OtpAlreadyVerifiedException('This request is already verified.');
        }

        $otp = $this->latestOtp($request);

        if (! $otp) {
            throw new OtpNotFoundException('No verification session was found. Please submit the form again.');
        }

        $otp = $this->otp->resend($otp, $ip);

        $this->log($request, 'otp_resent');

        return $otp;
    }

    public function latestOtp(CustomerRequest $request): ?OtpVerification
    {
        return $this->otp->latestFor($request);
    }

    public function latestOtpSeconds(CustomerRequest $request): int
    {
        $otp = $this->latestOtp($request);

        return $otp ? $this->otp->secondsUntilResend($otp) : 0;
    }

    public function updateStatus(CustomerRequest $request, RequestStatus $status, ?User $user = null): CustomerRequest
    {
        $request->update(['status' => $status]);

        $this->log($request, 'status_changed', ['status' => $status->value], $user);

        return $request;
    }

    public function addNote(CustomerRequest $request, string $note, ?User $user = null, bool $internal = true): RequestNote
    {
        $requestNote = $request->notes()->create([
            'user_id' => $user?->id,
            'note' => $note,
            'is_internal' => $internal,
        ]);

        $this->log($request, 'note_added', ['internal' => $internal], $user);

        return $requestNote;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(CustomerRequest $request, string $event, array $properties = [], ?User $user = null): void
    {
        $activity = activity('request')->performedOn($request);

        if ($user) {
            $activity->causedBy($user);
        }

        $activity->withProperties(Arr::add($properties, 'event', $event))->log($event);
    }
}
