<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Vehicle;
use App\Services\Requests\EnquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EnquiryController extends Controller
{
    public function store(StoreEnquiryRequest $request, EnquiryService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $vehicle = isset($validated['vehicle_id'])
            ? Vehicle::query()->published()->find($validated['vehicle_id'])
            : null;

        $enquiry = $service->createForVehicle($vehicle, $validated, $request->ip(), 'web');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Thanks — your enquiry has been received. Our team will contact you shortly.',
                'id' => $enquiry->uuid,
            ], 201);
        }

        return back()->with('status', 'Thanks — your enquiry has been received. Our team will contact you shortly.');
    }
}
