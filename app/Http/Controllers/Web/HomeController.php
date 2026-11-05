<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Catalog\VehicleCatalogService;
use App\Services\Seo\SeoService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(VehicleCatalogService $catalog, SeoService $seo): View
    {
        return view('home', [
            'data' => $catalog->home(),
            'options' => $catalog->filterOptions(),
            'seo' => $seo->forHome(),
        ]);
    }
}
