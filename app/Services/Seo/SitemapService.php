<?php

namespace App\Services\Seo;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Vehicle;
use App\Services\Support\CacheService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * Builds the public XML sitemap from indexable URLs only.
 *
 * Filtered listing URLs, OTP pages, drafts and empty landing pages are
 * intentionally excluded because those pages either emit noindex or 404.
 */
class SitemapService
{
    public function __construct(private CacheService $cache) {}

    public function render(): string
    {
        return $this->cache->remember(
            'seo:sitemap',
            fn () => $this->build()->render(),
            (int) config('seo.sitemap_cache_minutes', 60) * 60
        );
    }

    public function build(): Sitemap
    {
        $sitemap = Sitemap::create();
        $now = now();

        $sitemap->add($this->url(route('home'), $now, Url::CHANGE_FREQUENCY_DAILY, 1.0));

        $staticPages = [
            'vehicles.index' => [0.9, Url::CHANGE_FREQUENCY_DAILY],
            'sell.create' => [0.5, Url::CHANGE_FREQUENCY_MONTHLY],
            'buy.create' => [0.5, Url::CHANGE_FREQUENCY_MONTHLY],
            'contact' => [0.5, Url::CHANGE_FREQUENCY_MONTHLY],
            'pages.about' => [0.4, Url::CHANGE_FREQUENCY_MONTHLY],
            'pages.privacy' => [0.2, Url::CHANGE_FREQUENCY_YEARLY],
            'pages.terms' => [0.2, Url::CHANGE_FREQUENCY_YEARLY],
        ];

        foreach ($staticPages as $route => [$priority, $frequency]) {
            $sitemap->add($this->url(route($route), $now, $frequency, $priority));
        }

        // One row per category/brand combination that actually has a published
        // vehicle. This keeps empty (noindex) landing pages out of the sitemap.
        $combinations = $this->publishedVehicles()
            ->select('category_id', 'brand_id')
            ->distinct()
            ->get();

        $categories = Category::query()
            ->active()
            ->whereIn('id', $combinations->pluck('category_id')->unique())
            ->get()
            ->keyBy('id');

        $brands = Brand::query()
            ->active()
            ->whereIn('id', $combinations->pluck('brand_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($categories as $category) {
            $sitemap->add($this->url(
                route('categories.show', $category->slug),
                $category->updated_at ?? $now,
                Url::CHANGE_FREQUENCY_WEEKLY,
                0.8
            ));
        }

        foreach ($brands as $brand) {
            $sitemap->add($this->url(
                route('brands.show', $brand->slug),
                $brand->updated_at ?? $now,
                Url::CHANGE_FREQUENCY_WEEKLY,
                0.7
            ));
        }

        foreach ($combinations as $combination) {
            $category = $categories->get($combination->category_id);
            $brand = $brands->get($combination->brand_id);

            if (! $category || ! $brand) {
                continue;
            }

            $lastModified = collect([$category->updated_at, $brand->updated_at])
                ->filter()
                ->max() ?? $now;

            $sitemap->add($this->url(
                route('catalog.category-brand', [$category->slug, $brand->slug]),
                $lastModified,
                Url::CHANGE_FREQUENCY_WEEKLY,
                0.7
            ));
        }

        $this->publishedVehicles()
            ->with(['brand:id,name,slug', 'vehicleModel:id,name,slug'])
            ->lazyById(500)
            ->each(function (Vehicle $vehicle) use ($sitemap) {
                if (! $vehicle->brand || ! $vehicle->vehicleModel) {
                    return;
                }

                $sitemap->add($this->url(
                    $vehicle->public_url,
                    $vehicle->updated_at ?? now(),
                    Url::CHANGE_FREQUENCY_WEEKLY,
                    0.6
                ));
            });

        return $sitemap;
    }

    public function writeTo(string $path): string
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->render());

        return $path;
    }

    public function forget(): void
    {
        $this->cache->forget('seo:sitemap');
    }

    private function publishedVehicles(): Builder
    {
        return Vehicle::query()
            ->published()
            ->whereHas('brand', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('vehicleModel', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('fuelType', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('city', fn (Builder $query) => $query->where('is_active', true));
    }

    private function url(string $url, DateTimeInterface $lastModified, string $frequency, float $priority): Url
    {
        return Url::create($url)
            ->setLastModificationDate($lastModified)
            ->setChangeFrequency($frequency)
            ->setPriority($priority);
    }
}
