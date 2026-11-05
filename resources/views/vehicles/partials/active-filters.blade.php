@php
    $query = $filters->toQuery();
    $base = request()->url();
    $isLanding = request()->routeIs('categories.show', 'catalog.category-brand', 'brands.show');

    $urlWithout = function (array $keys) use ($query, $base): string {
        $next = $query;

        foreach ($keys as $key) {
            unset($next[$key]);
        }

        return $base.($next !== [] ? '?'.http_build_query($next) : '');
    };

    $labelFor = function (string $group, string $value, string $fallback): string {
        foreach ($options[$group] ?? [] as $option) {
            if (($option['slug'] ?? $option['value'] ?? null) === $value) {
                return $option['name'] ?? $option['label'] ?? $fallback;
            }
        }

        return $fallback;
    };

    $chips = [];

    if ($filters->keyword) {
        $chips[] = ['label' => 'Search: '.$filters->keyword, 'keys' => ['q'], 'url' => $urlWithout(['q'])];
    }

    if (! $isLanding && $filters->category) {
        $chips[] = ['label' => $labelFor('categories', $filters->category, ucfirst($filters->category)), 'keys' => ['category'], 'url' => $urlWithout(['category'])];
    }

    if (! $isLanding && $filters->brand) {
        $chips[] = ['label' => $labelFor('brands', $filters->brand, ucfirst($filters->brand)), 'keys' => ['brand'], 'url' => $urlWithout(['brand'])];
    }

    if ($filters->city) {
        $chips[] = ['label' => $labelFor('cities', $filters->city, ucfirst($filters->city)), 'keys' => ['city'], 'url' => $urlWithout(['city'])];
    }

    if ($filters->fuel) {
        $chips[] = ['label' => $labelFor('fuel_types', $filters->fuel, ucfirst($filters->fuel)), 'keys' => ['fuel'], 'url' => $urlWithout(['fuel'])];
    }

    if ($filters->transmission) {
        $chips[] = ['label' => $labelFor('transmissions', $filters->transmission, ucfirst($filters->transmission)), 'keys' => ['transmission'], 'url' => $urlWithout(['transmission'])];
    }

    if ($filters->condition) {
        $chips[] = ['label' => $labelFor('conditions', $filters->condition, ucfirst($filters->condition)), 'keys' => ['condition'], 'url' => $urlWithout(['condition'])];
    }

    if ($filters->colour) {
        $chips[] = ['label' => $filters->colour, 'keys' => ['colour'], 'url' => $urlWithout(['colour'])];
    }

    $priceKeys = [];

    if ($filters->minPrice !== null) {
        $priceKeys[] = 'min_price';
    }

    if ($filters->maxPrice !== null) {
        $priceKeys[] = 'max_price';
    }

    if ($priceKeys !== []) {
        $priceLabel = match (true) {
            $filters->minPrice !== null && $filters->maxPrice !== null => format_inr($filters->minPrice).' – '.format_inr($filters->maxPrice),
            $filters->maxPrice !== null => 'Under '.format_inr($filters->maxPrice),
            default => 'Above '.format_inr($filters->minPrice),
        };

        $chips[] = ['label' => $priceLabel, 'keys' => $priceKeys, 'url' => $urlWithout($priceKeys)];
    }

    $yearKeys = [];

    if ($filters->minYear !== null) {
        $yearKeys[] = 'min_year';
    }

    if ($filters->maxYear !== null) {
        $yearKeys[] = 'max_year';
    }

    if ($yearKeys !== []) {
        $yearLabel = match (true) {
            $filters->minYear !== null && $filters->maxYear !== null => $filters->minYear.' – '.$filters->maxYear,
            $filters->maxYear !== null => 'Up to '.$filters->maxYear,
            default => 'From '.$filters->minYear,
        };

        $chips[] = ['label' => $yearLabel, 'keys' => $yearKeys, 'url' => $urlWithout($yearKeys)];
    }

    if ($filters->maxKm !== null) {
        $chips[] = ['label' => 'Under '.number_format($filters->maxKm).' km', 'keys' => ['max_km'], 'url' => $urlWithout(['max_km'])];
    }

    if ($filters->seats !== null) {
        $chips[] = ['label' => $filters->seats.'+ seats', 'keys' => ['seats'], 'url' => $urlWithout(['seats'])];
    }

    if ($filters->owners !== null) {
        $chips[] = ['label' => $labelFor('owners', (string) $filters->owners, 'Up to '.$filters->owners.' owners'), 'keys' => ['owners'], 'url' => $urlWithout(['owners'])];
    }

    if ($filters->featured) {
        $chips[] = ['label' => 'Featured only', 'keys' => ['featured'], 'url' => $urlWithout(['featured'])];
    }

    $clearUrl = $base;
@endphp

@if ($chips !== [])
    <div class="mb-5 flex flex-wrap items-center gap-2" aria-label="Active filters">
        <span class="text-[11px] font-black uppercase tracking-wide text-ink-400">Active</span>

        @foreach ($chips as $chip)
            <a
                href="{{ $chip['url'] }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-bold text-ink-700 transition hover:border-ink hover:bg-ink-50"
                aria-label="Remove filter: {{ $chip['label'] }}"
            >
                {{ $chip['label'] }}
                <x-wd-icon name="close" size="xs" class="text-ink-400" />
            </a>
        @endforeach

        <a href="{{ $clearUrl }}" class="ml-1 text-xs font-bold text-ink-500 underline decoration-ink-300 underline-offset-4 hover:text-ink">
            Clear all
        </a>
    </div>
@endif
