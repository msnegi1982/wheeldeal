<?php

namespace App\Services\Catalog;

use App\DTOs\VehicleFilterData;
use App\Enums\Transmission;
use App\Enums\VehicleCondition;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\FuelType;
use App\Models\Vehicle;
use App\Services\Support\CacheService;
use Illuminate\Database\Eloquent\Builder;

class FilterOptionService
{
    public function __construct(private CacheService $cache) {}

    /**
     * All filter controls in a single cached payload. Counts are intentionally
     * limited to the high-traffic facets (brand/category/fuel) to keep the
     * cache build cheap.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->cache->remember('filter-options', fn () => $this->build(), (int) config('marketplace.cache.ttl', 600));
    }

    /**
     * @return array<string, mixed>
     */
    public function optionsForForm(): array
    {
        $options = $this->all();

        unset($options['price_buckets'], $options['sorts']);

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $published = fn (Builder $query) => $query->published();

        $categories = Category::query()
            ->active()
            ->withCount(['vehicles as published_vehicles_count' => $published])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'count' => (int) $category->published_vehicles_count,
            ])
            ->values()
            ->all();

        $brands = Brand::query()
            ->active()
            ->with('media')
            ->withCount(['vehicles as published_vehicles_count' => $published])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo_url' => $brand->logo_url,
                'initials' => $brand->initials,
                'count' => (int) $brand->published_vehicles_count,
            ])
            ->values()
            ->all();

        $cities = City::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'state' => $city->state,
            ])
            ->values()
            ->all();

        $fuelTypes = FuelType::query()
            ->active()
            ->withCount(['vehicles as published_vehicles_count' => $published])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FuelType $fuelType) => [
                'id' => $fuelType->id,
                'name' => $fuelType->name,
                'slug' => $fuelType->slug,
                'count' => (int) $fuelType->published_vehicles_count,
            ])
            ->values()
            ->all();

        $priceBounds = $this->publishedVehicleQuery()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $yearBounds = $this->publishedVehicleQuery()
            ->selectRaw('MIN(year) as min_year, MAX(year) as max_year')
            ->first();

        $seats = $this->publishedVehicleQuery()
            ->distinct()
            ->orderBy('seats')
            ->pluck('seats')
            ->map(fn ($seat) => (int) $seat)
            ->values()
            ->all();

        $colours = $this->publishedVehicleQuery()
            ->whereNotNull('colour')
            ->distinct()
            ->orderBy('colour')
            ->limit(24)
            ->pluck('colour')
            ->values()
            ->all();

        $currentYear = (int) date('Y');
        $maxYear = max($currentYear, (int) ($yearBounds->max_year ?? $currentYear));
        $minYear = max(1980, (int) ($yearBounds->min_year ?? 2000));

        return [
            'categories' => $categories,
            'brands' => $brands,
            'cities' => $cities,
            'fuel_types' => $fuelTypes,
            'transmissions' => collect(Transmission::options())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'conditions' => collect(VehicleCondition::options())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'seats' => $seats ?: [4, 5, 6, 7],
            'colours' => $colours,
            'price' => [
                'min' => (int) ($priceBounds->min_price ?? 0),
                'max' => (int) ($priceBounds->max_price ?? 10000000),
            ],
            'price_buckets' => [
                ['label' => 'Under ₹5 lakh', 'min' => null, 'max' => 500000],
                ['label' => '₹5 – 10 lakh', 'min' => 500000, 'max' => 1000000],
                ['label' => '₹10 – 20 lakh', 'min' => 1000000, 'max' => 2000000],
                ['label' => '₹20 – 50 lakh', 'min' => 2000000, 'max' => 5000000],
                ['label' => 'Above ₹50 lakh', 'min' => 5000000, 'max' => null],
            ],
            'years' => [
                'min' => $minYear,
                'max' => $maxYear,
                'options' => range($maxYear, $minYear),
            ],
            'owners' => [
                ['value' => 1, 'label' => 'First owner'],
                ['value' => 2, 'label' => 'Up to 2 owners'],
                ['value' => 3, 'label' => 'Up to 3 owners'],
            ],
            'sorts' => VehicleFilterData::SORTS,
            'per_page' => VehicleFilterData::PER_PAGE,
        ];
    }

    private function publishedVehicleQuery(): Builder
    {
        return Vehicle::query()
            ->published()
            ->whereHas('brand', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('vehicleModel', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true));
    }
}
