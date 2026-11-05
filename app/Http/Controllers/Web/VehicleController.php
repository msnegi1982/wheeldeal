<?php

namespace App\Http\Controllers\Web;

use App\DTOs\VehicleFilterData;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Vehicle;
use App\Services\Catalog\VehicleCatalogService;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        private VehicleCatalogService $catalog,
        private SeoService $seo,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $filters = VehicleFilterData::fromRequest($request);
        $vehicles = $this->catalog->listing($filters);
        $options = $this->catalog->filterOptions();
        $seo = $this->seo->forListing($filters);

        if ($this->wantsPartial($request)) {
            return $this->partial($request, $vehicles, $options, $filters);
        }

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'options' => $options,
            'filters' => $filters,
            'seo' => $seo,
        ]);
    }

    public function category(Request $request, string $category): View|JsonResponse
    {
        $categoryModel = Category::query()->active()->where('slug', $category)->firstOrFail();
        $filters = $this->landingFilters($request, $categoryModel->slug, null);
        $data = $this->catalog->landing($categoryModel, null, $filters);
        $options = $this->catalog->filterOptions();
        $seo = $this->seo->forLanding($categoryModel, null, $data['seoPage'], $data['vehicles']->total());

        if ($this->wantsPartial($request)) {
            return $this->partial($request, $data['vehicles'], $options, $filters);
        }

        return view('vehicles.landing', array_merge($data, [
            'options' => $options,
            'filters' => $filters,
            'seo' => $seo,
        ]));
    }

    public function categoryBrand(Request $request, string $category, string $brand): View|JsonResponse
    {
        $categoryModel = Category::query()->active()->where('slug', $category)->firstOrFail();
        $brandModel = Brand::query()->active()->where('slug', $brand)->firstOrFail();

        $filters = $this->landingFilters($request, $categoryModel->slug, $brandModel->slug);
        $data = $this->catalog->landing($categoryModel, $brandModel, $filters);
        $options = $this->catalog->filterOptions();
        $seo = $this->seo->forLanding($categoryModel, $brandModel, $data['seoPage'], $data['vehicles']->total());

        if ($this->wantsPartial($request)) {
            return $this->partial($request, $data['vehicles'], $options, $filters);
        }

        return view('vehicles.landing', array_merge($data, [
            'options' => $options,
            'filters' => $filters,
            'seo' => $seo,
        ]));
    }

    public function brand(Request $request, string $brand): View|JsonResponse
    {
        $brandModel = Brand::query()->active()->where('slug', $brand)->firstOrFail();
        $filters = $this->landingFilters($request, null, $brandModel->slug);
        $data = $this->catalog->brandLanding($brandModel, $filters);
        $options = $this->catalog->filterOptions();
        $seo = $this->seo->forBrand($brandModel, $data['vehicles']->total());

        if ($this->wantsPartial($request)) {
            return $this->partial($request, $data['vehicles'], $options, $filters);
        }

        return view('vehicles.landing', array_merge($data, [
            'options' => $options,
            'filters' => $filters,
            'seo' => $seo,
        ]));
    }

    public function show(Request $request, string $brand, string $model, string $vehicle): View
    {
        $vehicleModel = $this->catalog->detail($brand, $model, $vehicle);

        abort_unless($vehicleModel instanceof Vehicle, 404);

        $this->trackView($request, $vehicleModel);

        return view('vehicles.show', [
            'vehicle' => $vehicleModel,
            'similar' => $this->catalog->similar($vehicleModel, (int) config('marketplace.pagination.related', 4)),
            'seo' => $this->seo->forVehicle($vehicleModel),
        ]);
    }

    /**
     * Keep route filters authoritative while allowing city/fuel/sort refinement.
     *
     * @return array<string, mixed>
     */
    private function landingFilters(Request $request, ?string $category, ?string $brand): VehicleFilterData
    {
        $query = $request->query();

        if ($category) {
            $query['category'] = $category;
        } else {
            unset($query['category']);
        }

        if ($brand) {
            $query['brand'] = $brand;
        } else {
            unset($query['brand']);
        }

        return VehicleFilterData::fromArray($query);
    }

    private function wantsPartial(Request $request): bool
    {
        return $request->ajax() || $request->boolean('ajax');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function partial(
        Request $request,
        LengthAwarePaginator $vehicles,
        array $options,
        VehicleFilterData $filters
    ): JsonResponse {
        $query = $filters->toQuery();

        if ($filters->page > 1) {
            $query['page'] = $filters->page;
        }

        return response()->json([
            'html' => view('vehicles._results', [
                'vehicles' => $vehicles,
                'options' => $options,
                'filters' => $filters,
            ])->render(),
            'count' => $vehicles->total(),
            'url' => $request->url().($query ? '?'.http_build_query($query) : ''),
        ]);
    }

    private function trackView(Request $request, Vehicle $vehicle): void
    {
        $key = 'vehicle-viewed:'.$vehicle->getKey();

        if (! $request->session()->has($key)) {
            $this->catalog->trackView($vehicle);
            $request->session()->put($key, true);
        }
    }
}
