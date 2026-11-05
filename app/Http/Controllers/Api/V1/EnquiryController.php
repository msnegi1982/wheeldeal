<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Resources\EnquiryResource;
use App\Models\Vehicle;
use App\Services\Requests\EnquiryService;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    public function store(StoreEnquiryRequest $request, EnquiryService $service): JsonResponse
    {
        $validated = $request->validated();

        $vehicle = isset($validated['vehicle_id'])
            ? Vehicle::query()->published()->find($validated['vehicle_id'])
            : null;

        $enquiry = $service->createForVehicle(
            $vehicle,
            $validated,
            $request->ip(),
            'api'
        );

        $enquiry->load('vehicle');

        return response()->json([
            'message' => 'Thanks — your enquiry has been received. Our team will contact you shortly.',
            'data' => new EnquiryResource($enquiry),
        ], 201);
    }
}
