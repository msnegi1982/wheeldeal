@extends('layouts.app')

@php
    $isCategory = $category !== null;
    $isBrandOnly = $category === null && $brand !== null;
@endphp

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-10">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div>
                    <p class="eyebrow">
                        @if ($isBrandOnly)
                            Brand showroom
                        @else
                            {{ $brand ? $brand->name.' · '.$category->name : $category->name }}
                        @endif
                    </p>
                    <h1 class="mt-2 max-w-3xl text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
                        {{ $h1 }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600 sm:text-base">
                        {{ $intro }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="badge-ink">{{ number_format($vehicles->total()) }} {{ \Illuminate\Support\Str::plural('car', $vehicles->total()) }}</span>
                        <span class="badge-soft"><x-wd-icon name="shield-check" size="xs" /> Detail-checked listings</span>
                        <span class="badge-soft"><x-wd-icon name="map-pin" size="xs" /> Across India</span>
                    </div>
                </div>

                @if ($isCategory && $brands->isNotEmpty())
                    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
                        <p class="text-[11px] font-black uppercase tracking-wide text-ink-400">Shop {{ $category->name }} by brand</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if ($brand)
                                <a href="{{ route('categories.show', $category->slug) }}" class="chip chip-active">All brands</a>
                            @endif

                            @foreach ($brands->take(10) as $brandOption)
                                <a
                                    href="{{ route('catalog.category-brand', [$category->slug, $brandOption->slug]) }}"
                                    @class(['chip', 'chip-active' => $brand && $brand->id === $brandOption->id])
                                >
                                    {{ $brandOption->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif ($isBrandOnly && $brands->isNotEmpty())
                    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
                        <p class="text-[11px] font-black uppercase tracking-wide text-ink-400">Other brands</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($brands->reject(fn ($option) => $option->id === $brand->id)->take(9) as $brandOption)
                                <a href="{{ route('brands.show', $brandOption->slug) }}" class="chip">{{ $brandOption->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div
        x-data="catalogFilters('{{ request()->url() }}')"
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
                aria-label="Filter {{ $h1 }}"
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
                        'action' => request()->url(),
                        'category' => $category,
                        'brand' => $brand,
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
