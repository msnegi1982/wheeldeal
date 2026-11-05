<?php

namespace App\Services\Catalog;

use App\DTOs\VehicleFilterData;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\SeoPage;
use App\Models\Vehicle;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use App\Services\Support\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VehicleCatalogService
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private BrandRepositoryInterface $brands,
        private CategoryRepositoryInterface $categories,
        private FilterOptionService $filterOptions,
        private CacheService $cache,
    ) {}

    /**
     * Data for the home page, cached as a whole so the hero, rails and brand
     * grid cost one cache read on repeat visits.
     *
     * @return array<string, mixed>
     */
    public function home(): array
    {
        return $this->cache->remember('home', function () {
            $featured = $this->vehicles->featured(6);

            if ($featured->isEmpty()) {
                $featured = $this->vehicles->latest(6);
            }

            $brands = $this->brands->featured(12);

            if ($brands->isEmpty()) {
                $brands = $this->brands->activeWithCounts()->take(12);
            }

            return [
                'featured' => $featured,
                'latest' => $this->vehicles->latest(8),
                'popular' => $this->vehicles->popular(4),
                'categories' => $this->categories->activeWithCounts(),
                'brands' => $brands,
                'stats' => [
                    'vehicles' => $this->vehicles->publishedCount(),
                    'brands' => Brand::query()->active()->count(),
                    'cities' => City::query()->active()->count(),
                    'sold' => Vehicle::query()->where('status', \App\Enums\VehicleStatus::SOLD->value)->count(),
                ],
            ];
        }, (int) config('marketplace.cache.home_ttl', 300));
    }

    public function listing(VehicleFilterData $filters): LengthAwarePaginator
    {
        if ($filters->hasActiveFilters() || $filters->page > 1 || $filters->sort !== 'latest') {
            return $this->vehicles->paginate($filters);
        }

        return $this->cache->remember(
            'listing:page:'.$filters->page.':per:'.$filters->perPage,
            fn () => $this->vehicles->paginate($filters),
            300
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function landing(Category $category, ?Brand $brand, VehicleFilterData $filters): array
    {
        $seoPage = SeoPage::query()
            ->active()
            ->where('category_id', $category->id)
            ->when($brand, fn ($query) => $query->where('brand_id', $brand->id), fn ($query) => $query->whereNull('brand_id'))
            ->first();

        $vehicles = $this->vehicles->paginate($filters);
        $brandName = $brand?->name;
        $categoryName = $category->name;

        $h1 = $seoPage?->h1
            ?: trim(implode(' ', array_filter(['Used', $brandName, $categoryName, 'cars for sale'])));

        $intro = $seoPage?->intro_copy
            ?: sprintf(
                'Browse %d verified used %s %s in India. Compare prices, kilometres, fuel types and ownership history, then connect directly with the seller through a secure enquiry.',
                $vehicles->total(),
                $brandName ? $brandName.' ' : '',
                $categoryName
            );

        return [
            'category' => $category,
            'brand' => $brand,
            'vehicles' => $vehicles,
            'brands' => $this->brands->forCategory($category),
            'seoPage' => $seoPage,
            'h1' => $h1,
            'intro' => $intro,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function brandLanding(Brand $brand, VehicleFilterData $filters): array
    {
        $vehicles = $this->vehicles->paginate($filters);

        $h1 = 'Used '.$brand->name.' cars for sale';
        $intro = sprintf(
            'Explore %d verified used %s cars with transparent pricing, real kilometres and complete ownership details.',
            $vehicles->total(),
            $brand->name
        );

        return [
            'category' => null,
            'brand' => $brand,
            'vehicles' => $vehicles,
            'brands' => $this->brands->activeWithCounts()->take(24),
            'seoPage' => null,
            'h1' => $h1,
            'intro' => $intro,
        ];
    }

    public function detail(string $brandSlug, string $modelSlug, string $vehicleSlug): ?Vehicle
    {
        return $this->vehicles->findPublishedForRoute($brandSlug, $modelSlug, $vehicleSlug);
    }

    public function findPublishedByUuid(string $uuid): ?Vehicle
    {
        return $this->vehicles->findPublishedByUuid($uuid);
    }

    public function similar(Vehicle $vehicle, int $limit = 4): Collection
    {
        return $this->vehicles->similar($vehicle, $limit);
    }

    public function trackView(Vehicle $vehicle): void
    {
        $this->vehicles->incrementViews($vehicle);
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return $this->filterOptions->all();
    }
}
