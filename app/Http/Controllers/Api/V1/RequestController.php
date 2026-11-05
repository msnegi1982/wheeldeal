<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OtpChannel;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequestRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\CustomerRequestResource;
use App\Models\CustomerRequest;
use App\Services\Requests\CustomerRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function store(StoreVehicleRequestRequest $request, CustomerRequestService $service): JsonResponse
    {
        $validated = $request->validated();
        $type = RequestType::from($validated['type']);
        $channel = OtpChannel::from($validated['channel']);

        $result = $service->submit(
            type: $type,
            data: $validated,
            photos: $request->file('photos', []),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            channel: $channel,
            source: 'api'
        );

        return response()->json([
            'message' => 'Request received. Enter the 6-digit code sent to your mobile to verify it.',
            'data' => new CustomerRequestResource($result['request']),
            'otp' => [
                'expires_at' => $result['otp']->expires_at?->toIso8601String(),
                'resend_after' => (int) config('otp.resend_cooldown_seconds', 30),
                'attempts' => (int) $result['otp']->max_attempts,
            ],
        ], 201);
    }

    public function show(CustomerRequest $customerRequest): CustomerRequestResource
    {
        $customerRequest->load(['city', 'brand', 'vehicleModel', 'variant']);

        return new CustomerRequestResource($customerRequest);
    }

    public function verify(
        VerifyOtpRequest $request,
        CustomerRequest $customerRequest,
        CustomerRequestService $service
    ): JsonResponse {
        $service->verifyOtp($customerRequest, (string) $request->validated('code'));

        $customerRequest->refresh()->load(['city', 'brand', 'vehicleModel', 'variant']);

        return response()->json([
            'message' => 'Mobile number verified. Our team will contact you shortly.',
            'data' => new CustomerRequestResource($customerRequest),
        ]);
    }

    public function resend(
        Request $request,
        CustomerRequest $customerRequest,
        CustomerRequestService $service
    ): JsonResponse {
        $otp = $service->resendOtp($customerRequest, $request->ip());

        return response()->json([
            'message' => 'A new verification code has been sent.',
            'otp' => [
                'expires_at' => $otp->expires_at?->toIso8601String(),
                'resend_after' => (int) config('otp.resend_cooldown_seconds', 30),
            ],
        ]);
    }
}
