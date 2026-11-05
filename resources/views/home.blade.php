@extends('layouts.app')

@php
    $featured = $data['featured'];
    $latest = $data['latest'];
    $popular = $data['popular'];
    $categories = $data['categories'];
    $brands = $data['brands'];
    $stats = $data['stats'];

    $heroVehicle = $featured->first() ?? $latest->first();
    $heroGallery = $heroVehicle?->gallery_images ?? [];
    $heroImage = $heroGallery[0] ?? null;

    $categoryIcons = [
        'suv' => 'car',
        'sedan' => 'car',
        'hatchback' => 'car',
        'muv' => 'users',
        'coupe' => 'star',
        'convertible' => 'sparkles',
        'ev' => 'sparkles',
    ];
@endphp

@section('content')
    <section class="relative overflow-hidden bg-ink text-white">
        <div class="absolute inset-0 bg-hero-grid opacity-40"></div>
        <div class="absolute -right-40 -top-40 h-[30rem] w-[30rem] rounded-full bg-brand-500/15 blur-3xl"></div>
        <div class="absolute -bottom-48 left-1/4 h-[26rem] w-[26rem] rounded-full bg-brand-500/10 blur-3xl"></div>

        <div class="container-page relative grid gap-12 py-14 lg:grid-cols-[1.08fr_0.92fr] lg:items-center lg:py-20">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.16em] text-brand-300">
                    <x-wd-icon name="shield-check" size="xs" />
                    Verified used cars · 360° views
                </span>

                <h1 class="mt-5 max-w-2xl text-4xl font-black leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $seo->h1 }}
                </h1>

                <p class="mt-5 max-w-xl text-base leading-7 text-ink-200 sm:text-lg">
                    Compare inspected SUVs, sedans, hatchbacks and EVs by budget, fuel and city. See the full story of every car, then talk to a real person — no pressure, no runaround.
                </p>

                <form action="{{ route('vehicles.index') }}" method="GET" class="mt-8 rounded-2xl bg-white p-3 shadow-2xl shadow-black/30">
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="relative">
                            <label for="hero-search" class="sr-only">Search by brand or model</label>
                            <x-wd-icon name="search" size="sm" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400" />
                            <input
                                type="search"
                                id="hero-search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Search brand or model"
                                class="input pl-10"
                            >
                        </div>

                        <div>
                            <label for="hero-brand" class="sr-only">Brand</label>
                            <select id="hero-brand" name="brand" class="input">
                                <option value="">Any brand</option>
                                @foreach ($options['brands'] as $brand)
                                    <option value="{{ $brand['slug'] }}" @selected(request('brand') === $brand['slug'])>{{ $brand['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="hero-city" class="sr-only">City</label>
                            <select id="hero-city" name="city" class="input">
                                <option value="">Any city</option>
                                @foreach ($options['cities'] as $city)
                                    <option value="{{ $city['slug'] }}" @selected(request('city') === $city['slug'])>{{ $city['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="hero-budget" class="sr-only">Budget</label>
                            <select id="hero-budget" name="max_price" class="input">
                                <option value="">Any budget</option>
                                <option value="500000" @selected(request('max_price') === '500000')>Under ₹5 lakh</option>
                                <option value="1000000" @selected(request('max_price') === '1000000')>Under ₹10 lakh</option>
                                <option value="2000000" @selected(request('max_price') === '2000000')>Under ₹20 lakh</option>
                                <option value="5000000" @selected(request('max_price') === '5000000')>Under ₹50 lakh</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-col gap-3 border-t border-ink-100 px-1 pt-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-black uppercase tracking-wide text-ink-400">Popular:</span>
                            <a href="{{ route('categories.show', 'suv') }}" class="chip">SUVs</a>
                            <a href="{{ route('categories.show', 'ev') }}" class="chip">EVs</a>
                            <a href="{{ route('vehicles.index', ['transmission' => 'automatic']) }}" class="chip">Automatic</a>
                            <a href="{{ route('vehicles.index', ['max_price' => 1000000]) }}" class="chip">Under ₹10 L</a>
                        </div>

                        <button type="submit" class="btn-primary w-full sm:w-auto">
                            <x-wd-icon name="search" size="sm" />
                            Search cars
                        </button>
                    </div>
                </form>

                <dl class="mt-8 grid max-w-lg grid-cols-3 gap-4">
                    <div class="border-l-2 border-brand-500 pl-3">
                        <dt class="text-[10px] font-black uppercase tracking-[0.16em] text-ink-400">Live listings</dt>
                        <dd class="mt-1 text-2xl font-black tracking-tight text-white">{{ number_format($stats['vehicles']) }}</dd>
                    </div>
                    <div class="border-l-2 border-brand-500 pl-3">
                        <dt class="text-[10px] font-black uppercase tracking-[0.16em] text-ink-400">Brands</dt>
                        <dd class="mt-1 text-2xl font-black tracking-tight text-white">{{ number_format($stats['brands']) }}</dd>
                    </div>
                    <div class="border-l-2 border-brand-500 pl-3">
                        <dt class="text-[10px] font-black uppercase tracking-[0.16em] text-ink-400">Cities</dt>
                        <dd class="mt-1 text-2xl font-black tracking-tight text-white">{{ number_format($stats['cities']) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="absolute -inset-6 rounded-[2rem] border border-white/10"></div>

                @if ($heroVehicle && $heroImage)
                    <a href="{{ $heroVehicle->public_url }}" class="group relative block overflow-hidden rounded-[1.75rem] border border-white/10 bg-ink-800 shadow-2xl">
                        <img
                            src="{{ $heroImage['url'] }}"
                            @if ($heroImage['srcset']) srcset="{{ $heroImage['srcset'] }}" sizes="(min-width: 1024px) 44vw, 92vw" @endif
                            alt="{{ $heroImage['alt'] }}"
                            width="{{ $heroImage['width'] }}"
                            height="{{ $heroImage['height'] }}"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            class="aspect-[4/3] w-full object-cover transition duration-700 group-hover:scale-[1.02]"
                        >
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink via-ink/70 to-transparent p-6 pt-20">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <span class="badge-brand">Featured</span>
                                    <p class="mt-3 text-xl font-black tracking-tight text-white">{{ $heroVehicle->full_name }}</p>
                                    <p class="mt-1 text-sm font-semibold text-ink-200">{{ $heroVehicle->formatted_km }} · {{ $heroVehicle->fuelType?->name }} · {{ $heroVehicle->city?->name }}</p>
                                </div>
                                <p class="shrink-0 text-2xl font-black text-brand-500">{{ format_inr($heroVehicle->price, true) }}</p>
                            </div>
                        </div>
                    </a>

                    <div class="absolute -bottom-8 -left-8 w-60 rounded-2xl border border-white/10 bg-white p-4 shadow-2xl">
                        <div class="flex items-center gap-2 text-ink">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-500">
                                <x-wd-icon name="shield-check" size="sm" />
                            </span>
                            <p class="text-xs font-black uppercase tracking-wide">Inspected listing</p>
                        </div>
                        <p class="mt-3 text-xs font-semibold leading-5 text-ink-500">
                            Specification summary, ownership history and real photos before you enquire.
                        </p>
                    </div>

                    @if ($heroVehicle->has_360)
                        <div class="absolute -right-4 top-6 flex items-center gap-2 rounded-xl bg-brand-500 px-3 py-2 text-xs font-black uppercase tracking-wide text-ink shadow-xl">
                            <x-wd-icon name="rotate" size="sm" /> 360° available
                        </div>
                    @endif
                @else
                    <div class="flex aspect-[4/3] items-center justify-center rounded-[1.75rem] border border-white/10 bg-white/5 text-brand-500">
                        <x-wd-icon name="car" class="h-24 w-24" />
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="border-b border-ink-100 bg-sand" aria-labelledby="category-heading">
        <div class="container-page py-10 lg:py-12">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Start with a body type</p>
                    <h2 id="category-heading" class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Find the shape that fits your life</h2>
                </div>
                <a href="{{ route('vehicles.index') }}" class="link inline-flex items-center gap-1.5 text-sm">
                    Browse all cars <x-wd-icon name="arrow-right" size="xs" />
                </a>
            </div>

            <div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="group rounded-2xl border border-ink-100 bg-white p-4 transition hover:-translate-y-0.5 hover:border-ink hover:shadow-card">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ink text-brand-500 transition group-hover:bg-brand-500 group-hover:text-ink">
                            <x-wd-icon :name="$categoryIcons[$category->slug] ?? 'car'" size="sm" />
                        </span>
                        <span class="mt-3 block text-sm font-black text-ink">{{ $category->name }}</span>
                        <span class="mt-0.5 block text-[11px] font-semibold text-ink-400">{{ number_format($category->published_vehicles_count) }} cars</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="featured-heading">
        <div class="container-page">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Hand-picked inventory</p>
                    <h2 id="featured-heading" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Featured cars worth a closer look</h2>
                </div>
                <a href="{{ route('vehicles.index', ['featured' => 1]) }}" class="link inline-flex items-center gap-1.5 text-sm">
                    View all featured <x-wd-icon name="arrow-right" size="xs" />
                </a>
            </div>

            @if ($featured->isNotEmpty())
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" :eager="$loop->first" />
                    @endforeach
                </div>
            @else
                @include('partials.empty-state', [
                    'title' => 'No featured cars yet',
                    'message' => 'New inventory is added every week. Browse the full catalogue while we prepare the next drop.',
                ])
            @endif
        </div>
    </section>

    <section id="how-it-works" class="border-y border-ink-100 bg-white" aria-labelledby="how-heading">
        <div class="container-page py-14 lg:py-18">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div>
                    <p class="eyebrow">How WheelDeal works</p>
                    <h2 id="how-heading" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Less guesswork. More confidence.</h2>
                    <p class="mt-4 max-w-md text-sm leading-7 text-ink-600">
                        We keep the process deliberately simple: clear listings, straight answers and a human who helps you move from shortlist to test drive.
                    </p>

                    <div class="mt-7 space-y-3">
                        @foreach ([
                            ['search', 'Browse and compare', 'Filter by what matters — budget, fuel, transmission, ownership and city.'],
                            ['phone', 'Enquire in one step', 'Send an enquiry or WhatsApp us. We respond during business hours, not with a bot.'],
                            ['key', 'Inspect and decide', 'Get the full history, arrange a viewing and take a proper test drive.'],
                        ] as [$icon, $title, $copy])
                            <div class="flex gap-3 rounded-xl border border-ink-100 bg-white p-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-ink">
                                    <x-wd-icon :name="$icon" size="sm" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-ink">{{ $title }}</p>
                                    <p class="mt-1 text-xs leading-5 text-ink-500">{{ $copy }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-ink text-white">
                    <div class="border-b border-white/10 p-6 sm:p-8">
                        <span class="badge-brand">Selling?</span>
                        <h3 class="mt-4 text-2xl font-black tracking-tight text-white sm:text-3xl">Get a fair valuation without the classifieds circus.</h3>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-ink-200">
                            Tell us the basics, verify your mobile number, and our team will call you to understand the car and agree next steps. No obligation to sell.
                        </p>
                    </div>
                    <div class="grid gap-0 sm:grid-cols-2">
                        <div class="border-b border-white/10 p-6 sm:border-b-0 sm:border-r">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-500">What we need</p>
                            <ul class="mt-4 space-y-2.5 text-sm font-semibold text-ink-200">
                                @foreach (['Brand, model and year', 'Kilometres driven', 'Expected price', 'A few photos (optional)'] as $item)
                                    <li class="flex items-start gap-2">
                                        <x-wd-icon name="check" size="xs" class="mt-0.5 text-brand-500" /> {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="p-6">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-500">What you get</p>
                            <ul class="mt-4 space-y-2.5 text-sm font-semibold text-ink-200">
                                @foreach (['A real valuation conversation', 'OTP-verified contact', 'No listing fees', 'You decide if you sell'] as $item)
                                    <li class="flex items-start gap-2">
                                        <x-wd-icon name="check" size="xs" class="mt-0.5 text-brand-500" /> {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('sell.create') }}" class="btn-primary mt-6 w-full">
                                Start your valuation
                                <x-wd-icon name="arrow-right" size="sm" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="brands-heading">
        <div class="container-page">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Shop by brand</p>
                    <h2 id="brands-heading" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">The names you already trust</h2>
                </div>
                <a href="{{ route('vehicles.index') }}" class="link inline-flex items-center gap-1.5 text-sm">
                    See every brand <x-wd-icon name="arrow-right" size="xs" />
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($brands as $brand)
                    <a href="{{ route('brands.show', $brand->slug) }}" class="group flex items-center gap-3 rounded-2xl border border-ink-100 bg-white p-3.5 transition hover:-translate-y-0.5 hover:border-ink hover:shadow-card">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-ink text-brand-500">
                            @if ($brand->logo_url)
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo" width="300" height="300" loading="lazy" decoding="async" class="h-full w-full object-contain p-1.5">
                            @else
                                <span class="text-xs font-black">{{ $brand->initials }}</span>
                            @endif
                        </span>
                        <span>
                            <span class="block text-sm font-black text-ink">{{ $brand->name }}</span>
                            <span class="mt-0.5 block text-[11px] font-semibold text-ink-400">{{ number_format($brand->published_vehicles_count) }} cars</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-y border-ink-100 bg-sand" aria-labelledby="latest-heading">
        <div class="container-page py-14 lg:py-18">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Just added</p>
                    <h2 id="latest-heading" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Latest arrivals</h2>
                </div>
                <a href="{{ route('vehicles.index', ['sort' => 'latest']) }}" class="link inline-flex items-center gap-1.5 text-sm">
                    Browse newest first <x-wd-icon name="arrow-right" size="xs" />
                </a>
            </div>

            @if ($latest->isNotEmpty())
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($latest as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($popular->isNotEmpty())
        <section class="section" aria-labelledby="popular-heading">
            <div class="container-page">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Most viewed</p>
                        <h2 id="popular-heading" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Cars buyers keep coming back to</h2>
                    </div>
                    <a href="{{ route('vehicles.index', ['sort' => 'popular']) }}" class="link inline-flex items-center gap-1.5 text-sm">
                        See popular cars <x-wd-icon name="arrow-right" size="xs" />
                    </a>
                </div>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($popular as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="border-t border-ink-100 bg-white" aria-labelledby="faq-heading">
        <div class="container-page py-14 lg:py-18">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <p class="eyebrow">Good to know</p>
                    <h2 id="faq-heading" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Questions we hear every day</h2>
                    <p class="mt-4 text-sm leading-7 text-ink-600">
                        Still unsure about something? Call or WhatsApp us — we would rather answer a question than have you guess.
                    </p>
                    <a href="{{ route('contact') }}" class="btn-outline mt-6">
                        Talk to the team
                        <x-wd-icon name="arrow-right" size="sm" />
                    </a>
                </div>

                <div class="space-y-3">
                    @foreach ([
                        ['Are the cars inspected?', 'Listings are reviewed before publishing and include the specification, ownership and condition details we have on record. We still recommend an independent inspection and test drive before purchase.'],
                        ['Do you charge buyers a fee?', 'Browsing and enquiring are free. If a transaction moves forward, any applicable service or facilitation fee is explained in writing before you commit.'],
                        ['Why do I need to verify my mobile number?', 'The six-digit code keeps spam and fake requests out, and lets us contact the right person about the right car. Codes expire after five minutes and are never shown in the listing.'],
                        ['Can I sell a car without photos?', 'Yes. Photos help us give a more accurate first opinion, but you can submit the request without them and share images later.'],
                    ] as [$question, $answer])
                        <details class="group rounded-xl border border-ink-100 bg-white p-4 open:border-ink-200">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-black text-ink [&::-webkit-details-marker]:hidden">
                                {{ $question }}
                                <x-wd-icon name="chevron-down" size="sm" class="shrink-0 text-ink-400 transition group-open:rotate-180" />
                            </summary>
                            <p class="mt-3 text-sm leading-7 text-ink-600">{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
