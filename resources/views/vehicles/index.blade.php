@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-10">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="eyebrow">Browse the catalogue</p>
                    <h1 class="mt-2 max-w-3xl text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
                        {{ $seo->h1 }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600 sm:text-base">
                        Every listing carries the details that matter: real kilometres, fuel type, transmission, ownership and city. Filter down to the handful worth a test drive.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <span class="badge-soft"><x-wd-icon name="shield-check" size="xs" /> Verified sellers</span>
                    <span class="badge-soft"><x-wd-icon name="rotate" size="xs" /> 360° views</span>
                    <span class="badge-soft"><x-wd-icon name="tag" size="xs" /> Transparent pricing</span>
                </div>
            </div>
        </div>
    </section>

    <div
        x-data="catalogFilters('{{ route('vehicles.index') }}')"
        class="container-page py-8 lg:py-10"
    >
        <div class="grid gap-8 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
            <div
                x-show="filtersOpen"
                x-cloak
                class="fixed inset-0 z-40 bg-ink/50 backdrop-blur-sm lg:hidden"
                @click="filtersOpen = false"
                aria-hidden="true"
            ></div>

            <aside
                id="catalog-filters"
                class="lg:sticky lg:top-24 lg:block"
                :class="filtersOpen
                    ? 'fixed inset-y-0 left-0 z-50 w-[min(22rem,92vw)] overflow-y-auto border-r border-ink-100 bg-white p-5 shadow-2xl lg:static lg:z-auto lg:w-auto lg:overflow-visible lg:border-0 lg:p-0 lg:shadow-none'
                    : 'hidden lg:block'"
                aria-label="Filter used cars"
            >
                <div class="mb-4 flex items-center justify-between lg:hidden">
                    <p class="text-sm font-black uppercase tracking-wide text-ink">Refine results</p>
                    <button type="button" class="btn-ghost btn-sm" @click="filtersOpen = false" aria-label="Close filters">
                        <x-wd-icon name="close" size="sm" />
                    </button>
                </div>

                <div class="card p-5 lg:p-6">
                    @include('vehicles.partials.filters', [
                        'options' => $options,
                        'filters' => $filters,
                        'action' => route('vehicles.index'),
                    ])
                </div>
            </aside>

            <section aria-labelledby="results-heading">
                <h2 id="results-heading" class="sr-only">Vehicle results</h2>

                <div class="mb-5 flex flex-col gap-3 border-b border-ink-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" class="btn-outline btn-sm lg:hidden" @click="filtersOpen = true">
                            <x-wd-icon name="sliders" size="xs" />
                            Filters
                        </button>

                        <p class="text-sm font-bold text-ink-600" aria-live="polite">
                            <span x-text="count === null ? {{ $vehicles->total() }} : count">{{ $vehicles->total() }}</span>
                            {{ \Illuminate\Support\Str::plural('car', $vehicles->total()) }}
                        </p>

                        <span x-show="loading" x-cloak class="inline-flex items-center gap-1.5 text-xs font-bold text-ink-400">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-brand-500"></span>
                            Updating
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="catalog-sort" class="shrink-0 text-xs font-black uppercase tracking-wide text-ink-500">Sort</label>
                        <select
                            id="catalog-sort"
                            name="sort"
                            form="catalog-filter-form"
                            x-ref="sortField"
                            class="input w-auto min-w-44 py-2 text-xs font-bold"
                            @change="fetchResults()"
                        >
                            @foreach ($options['sorts'] as $value => $label)
                                <option value="{{ $value }}" @selected($filters->sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-ref="results">
                    @include('vehicles._results', [
                        'vehicles' => $vehicles,
                        'options' => $options,
                        'filters' => $filters,
                    ])
                </div>
            </section>
        </div>
    </div>
@endsection
