<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Services\Requests\EnquiryService;
use App\Services\Seo\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(private SeoService $seo) {}

    public function show(): View
    {
        return view('pages.contact', [
            'seo' => $this->seo->forPage(
                'Contact us',
                'Questions about buying or selling a used car? Call, WhatsApp or message the WheelDeal team.',
                '/contact'
            ),
        ]);
    }

    public function store(
        StoreContactEnquiryRequest $request,
        EnquiryService $service
    ): RedirectResponse|JsonResponse {
        $enquiry = $service->createContact($request->validated(), $request->ip(), 'web');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Thanks for reaching out. We will get back to you within one working day.',
                'id' => $enquiry->uuid,
            ], 201);
        }

        return back()->with('status', 'Thanks for reaching out. We will get back to you within one working day.');
    }
}
