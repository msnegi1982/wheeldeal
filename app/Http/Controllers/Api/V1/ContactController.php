<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Http\Resources\ContactEnquiryResource;
use App\Services\Requests\EnquiryService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreContactEnquiryRequest $request, EnquiryService $service): JsonResponse
    {
        $enquiry = $service->createContact($request->validated(), $request->ip(), 'api');

        return response()->json([
            'message' => 'Thanks for reaching out. We will get back to you within one working day.',
            'data' => new ContactEnquiryResource($enquiry),
        ], 201);
    }
}
