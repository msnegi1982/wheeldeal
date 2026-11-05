<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoService;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function __construct(private SeoService $seo) {}

    public function about(): View
    {
        return view('pages.about', [
            'seo' => $this->seo->forPage(
                'About WheelDeal',
                'WheelDeal makes buying and selling used four-wheelers in India simpler, safer and more transparent.',
                '/about'
            ),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.privacy', [
            'seo' => $this->seo->forPage(
                'Privacy policy',
                'How WheelDeal collects, uses and protects the personal information you share with us.',
                '/privacy'
            ),
        ]);
    }

    public function terms(): View
    {
        return view('pages.terms', [
            'seo' => $this->seo->forPage(
                'Terms of use',
                'The terms that apply when you browse WheelDeal, submit a vehicle request or contact our team.',
                '/terms'
            ),
        ]);
    }
}
