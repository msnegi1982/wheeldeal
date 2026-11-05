<?php

namespace App\Http\Controllers\Web;

use App\Enums\OtpChannel;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequestRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\CustomerRequest;
use App\Models\VehicleModel;
use App\Services\Catalog\VehicleCatalogService;
use App\Services\Requests\CustomerRequestService;
use App\Services\Seo\SeoService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellBuyController extends Controller
{
    public function __construct(
        private CustomerRequestService $requests,
        private VehicleCatalogService $catalog,
        private SeoService $seo,
    ) {}

    public function create(string $type): View
    {
        $requestType = $this->type($type);

        return view('requests.form', [
            'type' => $requestType,
            'options' => $this->catalog->filterOptions(),
            'models' => VehicleModel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'brand_id', 'name']),
            'seo' => $this->seo->forPage(
                $requestType === RequestType::SELL ? 'Sell your car' : 'Tell us what you want to buy',
                $requestType === RequestType::SELL
                    ? 'Get a fair, no-obligation valuation for your used car. Verify your mobile number and our team will call you.'
                    : 'Share your budget and must-haves. We will shortlist verified used cars that fit.',
                $requestType === RequestType::SELL ? '/sell-vehicle' : '/buy-vehicle'
            ),
        ]);
    }

    public function store(
        StoreVehicleRequestRequest $request,
        string $type,
        CustomerRequestService $service
    ): RedirectResponse {
        $validated = $request->validated();

        $result = $service->submit(
            type: $this->type($type),
            data: $validated,
            photos: $request->file('photos', []),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            channel: OtpChannel::from($validated['channel']),
            source: 'web'
        );

        return redirect()
            ->route('requests.verify', $result['request'])
            ->with('status', 'We sent a 6-digit verification code to '.PhoneNumber::masked($result['request']->phone).'.');
    }

    public function verify(CustomerRequest $customerRequest): View|RedirectResponse
    {
        if ($customerRequest->isVerified()) {
            return redirect()->route('requests.success', $customerRequest);
        }

        $customerRequest->loadMissing(['city', 'brand', 'vehicleModel', 'variant']);

        $otp = $this->requests->latestOtp($customerRequest);

        return view('requests.verify', [
            'customerRequest' => $customerRequest,
            'otp' => $otp,
            'maskedPhone' => PhoneNumber::masked($customerRequest->phone),
            'resendAfter' => $otp ? $this->requests->latestOtpSeconds($customerRequest) : 0,
            'seo' => $this->seo->forPage(
                'Verify your mobile number',
                'Enter the 6-digit code sent to your mobile to confirm your request.',
                '/verify-otp/'.$customerRequest->uuid,
                'noindex,nofollow'
            ),
        ]);
    }

    public function verifySubmit(
        VerifyOtpRequest $request,
        CustomerRequest $customerRequest,
        CustomerRequestService $service
    ): RedirectResponse {
        $service->verifyOtp($customerRequest, (string) $request->validated('code'));

        return redirect()
            ->route('requests.success', $customerRequest)
            ->with('verified', true);
    }

    public function resend(Request $request, CustomerRequest $customerRequest, CustomerRequestService $service): RedirectResponse
    {
        $service->resendOtp($customerRequest, $request->ip());

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function success(CustomerRequest $customerRequest): View|RedirectResponse
    {
        if (! $customerRequest->isVerified()) {
            return redirect()->route('requests.verify', $customerRequest);
        }

        $customerRequest->load(['city', 'brand', 'vehicleModel', 'variant']);

        return view('requests.success', [
            'customerRequest' => $customerRequest,
            'seo' => $this->seo->forPage(
                'Request received',
                'Your request has been verified. Our team will contact you shortly.',
                '/verify-otp/'.$customerRequest->uuid.'/success',
                'noindex,nofollow'
            ),
        ]);
    }

    private function type(string $type): RequestType
    {
        return $type === 'buy' ? RequestType::BUY : RequestType::SELL;
    }
}
