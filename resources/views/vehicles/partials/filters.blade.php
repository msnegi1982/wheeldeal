@php
    $action = $action ?? route('vehicles.index');
    $lockCategory = isset($category) && $category;
    $lockBrand = isset($brand) && $brand;
@endphp

<form
    id="catalog-filter-form"
    x-ref="filterForm"
    action="{{ $action }}"
    method="GET"
    class="space-y-0"
    @change="schedule()"
    @input.debounce.500ms="schedule()"
    @submit.prevent="fetchResults()"
>
    <input type="hidden" name="per_page" value="{{ $filters->perPage }}">

    <div class="flex items-center justify-between gap-3 border-b border-ink-100 pb-4">
        <div class="flex items-center gap-2">
            <x-wd-icon name="sliders" size="sm" class="text-ink-400" />
            <h2 class="text-sm font-black uppercase tracking-wide text-ink">Filters</h2>
        </div>
        <button type="reset" class="text-xs font-bold text-ink-500 underline decoration-ink-300 underline-offset-4 hover:text-ink" @click.prevent="reset()">
            Clear all
        </button>
    </div>

    <div class="filter-section">
        <label for="filter-q" class="label">Keyword</label>
        <div class="relative">
            <x-wd-icon name="search" size="sm" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400" />
            <input type="search" id="filter-q" name="q" value="{{ $filters->keyword }}" placeholder="Brand, model or colour" class="input pl-10">
        </div>
    </div>

    <div class="filter-section">
        <span class="label">Body type</span>
        @if ($lockCategory)
            <input type="hidden" name="category" value="{{ $category->slug }}" data-locked="true">
            <div class="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-3.5 py-2.5">
                <span class="text-sm font-bold text-ink">{{ $category->name }}</span>
                <a href="{{ route('vehicles.index') }}" class="text-xs font-bold text-ink-500 underline underline-offset-4 hover:text-ink">Change</a>
            </div>
        @else
            <select id="filter-category" name="category" class="input">
                <option value="">All body types</option>
                @foreach ($options['categories'] as $categoryOption)
                    <option value="{{ $categoryOption['slug'] }}" @selected($filters->category === $categoryOption['slug'])>
                        {{ $categoryOption['name'] }} ({{ $categoryOption['count'] }})
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="filter-section">
        <span class="label">Brand</span>
        @if ($lockBrand)
            <input type="hidden" name="brand" value="{{ $brand->slug }}" data-locked="true">
            <div class="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-3.5 py-2.5">
                <span class="text-sm font-bold text-ink">{{ $brand->name }}</span>
                @if ($lockCategory)
                    <a href="{{ route('categories.show', $category->slug) }}" class="text-xs font-bold text-ink-500 underline underline-offset-4 hover:text-ink">All brands</a>
                @else
                    <a href="{{ route('vehicles.index') }}" class="text-xs font-bold text-ink-500 underline underline-offset-4 hover:text-ink">Change</a>
                @endif
            </div>
        @else
            <select id="filter-brand" name="brand" class="input">
                <option value="">All brands</option>
                @foreach ($options['brands'] as $brandOption)
                    <option value="{{ $brandOption['slug'] }}" @selected($filters->brand === $brandOption['slug'])>
                        {{ $brandOption['name'] }} ({{ $brandOption['count'] }})
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="filter-section">
        <label for="filter-city" class="label">City</label>
        <select id="filter-city" name="city" class="input">
            <option value="">All cities</option>
            @foreach ($options['cities'] as $city)
                <option value="{{ $city['slug'] }}" @selected($filters->city === $city['slug'])>{{ $city['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-section">
        <span class="label">Budget</span>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="filter-min-price" class="sr-only">Minimum price</label>
                <input type="number" inputmode="numeric" id="filter-min-price" name="min_price" x-ref="minPrice" value="{{ $filters->minPrice }}" min="0" step="10000" placeholder="Min ₹" class="input">
            </div>
            <div>
                <label for="filter-max-price" class="sr-only">Maximum price</label>
                <input type="number" inputmode="numeric" id="filter-max-price" name="max_price" x-ref="maxPrice" value="{{ $filters->maxPrice }}" min="0" step="10000" placeholder="Max ₹" class="input">
            </div>
        </div>

        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($options['price_buckets'] as $bucket)
                <button
                    type="button"
                    class="chip"
                    @click="$refs.minPrice.value = '{{ $bucket['min'] }}'; $refs.maxPrice.value = '{{ $bucket['max'] }}'; fetchResults()"
                >
                    {{ $bucket['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="filter-section">
        <label for="filter-fuel" class="label">Fuel type</label>
        <select id="filter-fuel" name="fuel" class="input">
            <option value="">Any fuel</option>
            @foreach ($options['fuel_types'] as $fuel)
                <option value="{{ $fuel['slug'] }}" @selected($filters->fuel === $fuel['slug'])>
                    {{ $fuel['name'] }} ({{ $fuel['count'] }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="filter-section">
        <label for="filter-transmission" class="label">Transmission</label>
        <select id="filter-transmission" name="transmission" class="input">
            <option value="">Any transmission</option>
            @foreach ($options['transmissions'] as $transmission)
                <option value="{{ $transmission['value'] }}" @selected($filters->transmission === $transmission['value'])>{{ $transmission['label'] }}</option>
            @endforeach
        </select>
    </div>

    <details class="filter-section group">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-black uppercase tracking-wide text-ink-600 [&::-webkit-details-marker]:hidden">
            More filters
            <x-wd-icon name="chevron-down" size="sm" class="text-ink-400 transition group-open:rotate-180" />
        </summary>

        <div class="mt-4 space-y-4">
            <div>
                <span class="label">Year</span>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="filter-min-year" class="sr-only">Minimum year</label>
                        <select id="filter-min-year" name="min_year" class="input">
                            <option value="">From</option>
                            @foreach ($options['years']['options'] as $year)
                                <option value="{{ $year }}" @selected($filters->minYear === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-max-year" class="sr-only">Maximum year</label>
                        <select id="filter-max-year" name="max_year" class="input">
                            <option value="">To</option>
                            @foreach ($options['years']['options'] as $year)
                                <option value="{{ $year }}" @selected($filters->maxYear === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label for="filter-max-km" class="label">Maximum kilometres</label>
                <input type="number" inputmode="numeric" id="filter-max-km" name="max_km" value="{{ $filters->maxKm }}" min="0" step="5000" placeholder="Any" class="input">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="filter-seats" class="label">Seats</label>
                    <select id="filter-seats" name="seats" class="input">
                        <option value="">Any</option>
                        @foreach ($options['seats'] as $seat)
                            <option value="{{ $seat }}" @selected($filters->seats === (int) $seat)>{{ $seat }}+</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-owners" class="label">Owners</label>
                    <select id="filter-owners" name="owners" class="input">
                        <option value="">Any</option>
                        @foreach ($options['owners'] as $owner)
                            <option value="{{ $owner['value'] }}" @selected($filters->owners === (int) $owner['value'])>{{ $owner['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="filter-condition" class="label">Condition</label>
                <select id="filter-condition" name="condition" class="input">
                    <option value="">Any condition</option>
                    @foreach ($options['conditions'] as $condition)
                        <option value="{{ $condition['value'] }}" @selected($filters->condition === $condition['value'])>{{ $condition['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-colour" class="label">Colour</label>
                <input type="text" id="filter-colour" name="colour" value="{{ $filters->colour }}" placeholder="e.g. White" class="input">
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-ink-100 bg-ink-50 px-3 py-2.5">
                <input type="checkbox" name="featured" value="1" @checked($filters->featured) class="rounded border-ink-300 text-ink focus:ring-brand-500">
                <span class="text-xs font-bold text-ink-700">Featured cars only</span>
            </label>
        </div>
    </details>

    <div class="border-t border-ink-100 pt-4">
        <button type="submit" class="btn-primary w-full">
            <x-wd-icon name="search" size="sm" />
            Apply filters
        </button>
        <button type="button" class="btn-ghost mt-2 w-full lg:hidden" @click="filtersOpen = false">
            Show results
            <x-wd-icon name="arrow-right" size="sm" />
        </button>
    </div>
</form>
