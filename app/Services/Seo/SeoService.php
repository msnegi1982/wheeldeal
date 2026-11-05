<?php

namespace App\Services\Seo;

use App\DTOs\SeoData;
use App\DTOs\VehicleFilterData;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SeoPage;
use App\Models\Vehicle;
use Illuminate\Support\Str;

class SeoService
{
    public function forHome(): SeoData
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => route('home')],
        ];

        return new SeoData(
            title: config('seo.default_title'),
            description: config('seo.default_description'),
            canonical: route('home'),
            image: $this->absolute(config('seo.default_image')),
            jsonLd: [
                $this->organizationSchema(),
                $this->websiteSchema(),
                $this->breadcrumbSchema($breadcrumbs),
            ],
            breadcrumbs: $breadcrumbs,
            h1: 'Find your next four-wheeler',
        );
    }

    public function forListing(VehicleFilterData $filters): SeoData
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Used cars', 'url' => route('vehicles.index')],
        ];

        $robots = $filters->hasActiveFilters() ? 'noindex,follow' : config('seo.robots', 'index,follow');

        return new SeoData(
            title: $filters->keyword
                ? 'Search results for “'.$filters->keyword.'” — '.config('seo.site_name')
                : 'Used cars for sale in India — '.config('seo.site_name'),
            description: $filters->hasActiveFilters()
                ? 'Refine used cars by brand, budget, fuel, city and ownership. Filtered views are excluded from search results; the unfiltered catalogue is the canonical page.'
                : config('seo.default_description'),
            canonical: route('vehicles.index'),
            robots: $robots,
            image: $this->absolute(config('seo.default_image')),
            jsonLd: [$this->breadcrumbSchema($breadcrumbs)],
            breadcrumbs: $breadcrumbs,
            h1: 'Used cars for sale',
        );
    }

    public function forLanding(
        Category $category,
        ?Brand $brand,
        ?SeoPage $seoPage,
        int $resultCount
    ): SeoData {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Used cars', 'url' => route('vehicles.index')],
            ['label' => $category->name, 'url' => route('categories.show', $category->slug)],
        ];

        if ($brand) {
            $breadcrumbs[] = [
                'label' => $brand->name,
                'url' => route('catalog.category-brand', [$category->slug, $brand->slug]),
            ];
        }

        $subject = trim(($brand?->name ?? '').' '.$category->name);
        $path = $brand
            ? route('catalog.category-brand', [$category->slug, $brand->slug])
            : route('categories.show', $category->slug);

        $title = $seoPage?->meta_title
            ?: 'Used '.$subject.' cars for sale — '.config('seo.site_name');

        $description = $seoPage?->meta_description
            ?: sprintf(
                'Browse %d verified used %s cars in India. Compare price, year, kilometres, fuel and ownership, and enquire securely on %s.',
                $resultCount,
                $subject,
                config('seo.site_name')
            );

        return new SeoData(
            title: $this->title($title),
            description: $this->description($description),
            canonical: $path,
            robots: $resultCount === 0 ? 'noindex,follow' : config('seo.robots', 'index,follow'),
            image: $this->absolute(config('seo.default_image')),
            jsonLd: [$this->breadcrumbSchema($breadcrumbs)],
            breadcrumbs: $breadcrumbs,
            h1: $seoPage?->h1 ?: 'Used '.$subject.' cars for sale',
        );
    }

    public function forBrand(Brand $brand, int $resultCount): SeoData
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Used cars', 'url' => route('vehicles.index')],
            ['label' => $brand->name, 'url' => route('brands.show', $brand->slug)],
        ];

        return new SeoData(
            title: $this->title($brand->meta_title ?: 'Used '.$brand->name.' cars for sale — '.config('seo.site_name')),
            description: $this->description($brand->meta_description ?: sprintf(
                'Explore %d verified used %s cars with transparent pricing, real kilometres and complete ownership details.',
                $resultCount,
                $brand->name
            )),
            canonical: route('brands.show', $brand->slug),
            robots: $resultCount === 0 ? 'noindex,follow' : config('seo.robots', 'index,follow'),
            image: $this->absolute(config('seo.default_image')),
            jsonLd: [$this->breadcrumbSchema($breadcrumbs)],
            breadcrumbs: $breadcrumbs,
            h1: 'Used '.$brand->name.' cars for sale',
        );
    }

    public function forVehicle(Vehicle $vehicle): SeoData
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Used cars', 'url' => route('vehicles.index')],
        ];

        if ($vehicle->category) {
            $breadcrumbs[] = [
                'label' => $vehicle->category->name,
                'url' => route('categories.show', $vehicle->category->slug),
            ];
        }

        if ($vehicle->brand) {
            $breadcrumbs[] = [
                'label' => $vehicle->brand->name,
                'url' => route('brands.show', $vehicle->brand->slug),
            ];
        }

        $breadcrumbs[] = [
            'label' => $vehicle->full_name ?: $vehicle->title,
            'url' => $vehicle->public_url,
        ];

        $defaultTitle = trim(sprintf(
            '%s — %s | %s',
            $vehicle->full_name ?: $vehicle->title,
            format_inr($vehicle->price, true),
            config('seo.site_name')
        ));

        $description = $vehicle->meta_description ?: Str::limit(
            strip_tags((string) ($vehicle->description ?: sprintf(
                '%s in %s. %s, %s, %s. %s. Enquire securely on %s.',
                $vehicle->full_name ?: $vehicle->title,
                $vehicle->city?->name ?? 'India',
                $vehicle->fuelType?->name ?? 'Petrol',
                $vehicle->transmission?->label() ?? 'Manual',
                $vehicle->formatted_km,
                $vehicle->owner_label,
                config('seo.site_name')
            ))),
            158
        );

        $image = $vehicle->primary_image_url
            ? $this->absolute($vehicle->primary_image_url)
            : $this->absolute($vehicle->og_image ?: config('seo.default_image'));

        return new SeoData(
            title: $this->title($vehicle->meta_title ?: $defaultTitle),
            description: $this->description($description),
            canonical: $vehicle->public_url,
            robots: config('seo.robots', 'index,follow'),
            image: $image,
            type: 'product',
            jsonLd: [
                $this->vehicleSchema($vehicle),
                $this->breadcrumbSchema($breadcrumbs),
            ],
            breadcrumbs: $breadcrumbs,
            h1: $vehicle->full_name ?: $vehicle->title,
        );
    }

    public function forPage(string $title, string $description, string $path, string $robots = 'index,follow'): SeoData
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $title, 'url' => $this->absolute($path)],
        ];

        return new SeoData(
            title: $this->title($title.' — '.config('seo.site_name')),
            description: $this->description($description),
            canonical: $this->absolute($path),
            robots: $robots,
            image: $this->absolute(config('seo.default_image')),
            jsonLd: [$this->breadcrumbSchema($breadcrumbs)],
            breadcrumbs: $breadcrumbs,
            h1: $title,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => route('home').'#organization',
            'name' => config('seo.site_name'),
            'url' => route('home'),
            'logo' => $this->absolute(config('seo.default_image')),
            'email' => config('marketplace.brand.email'),
            'telephone' => config('marketplace.brand.phone'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('marketplace.brand.address'),
                'addressCountry' => 'IN',
            ],
            'sameAs' => array_values(array_filter([
                config('marketplace.social.facebook'),
                config('marketplace.social.instagram'),
                config('marketplace.social.youtube'),
                config('marketplace.social.x'),
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => route('home').'#website',
            'name' => config('seo.site_name'),
            'url' => route('home'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('vehicles.index').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function breadcrumbSchema(array $breadcrumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)
                ->values()
                ->map(fn (array $item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function vehicleSchema(Vehicle $vehicle): array
    {
        $images = collect($vehicle->gallery_images)->pluck('url')->filter()->map(fn ($url) => $this->absolute($url))->values();
        $canonical = $vehicle->public_url;

        if ($vehicle->primary_image_url) {
            $images->prepend($this->absolute($vehicle->primary_image_url));
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Car',
            'name' => $vehicle->full_name ?: $vehicle->title,
            'url' => $canonical,
            'description' => Str::limit(strip_tags((string) $vehicle->description), 300),
            'brand' => [
                '@type' => 'Brand',
                'name' => $vehicle->brand?->name,
            ],
            'model' => $vehicle->vehicleModel?->name,
            'vehicleModelDate' => (string) $vehicle->year,
            'bodyType' => $vehicle->category?->name,
            'color' => $vehicle->colour,
            'fuelType' => $vehicle->fuelType?->name,
            'vehicleTransmission' => $vehicle->transmission?->label(),
            'vehicleSeatingCapacity' => $vehicle->seats,
            'mileageFromOdometer' => [
                '@type' => 'QuantitativeValue',
                'value' => (int) $vehicle->km_driven,
                'unitCode' => 'KMT',
            ],
            'image' => $images->unique()->take(6)->values()->all(),
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonical,
                'price' => (string) (int) $vehicle->price,
                'priceCurrency' => 'INR',
                'availability' => 'https://schema.org/InStock',
                'itemCondition' => 'https://schema.org/UsedCondition',
                'seller' => [
                    '@type' => 'AutoDealer',
                    'name' => config('seo.site_name'),
                    'url' => route('home'),
                ],
            ],
        ];
    }

    public function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }

    private function title(string $title): string
    {
        return Str::limit(trim($title), 65, '…');
    }

    private function description(string $description): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description)) ?? ''), 158, '…');
    }
}
