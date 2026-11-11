@extends('layouts.app')

@php
    $title = $vehicle->full_name ?: $vehicle->title;
    $gallery = $vehicle->gallery_images;
    $frames = $vehicle->has_360 ? $vehicle->frame_urls : [];
    $primary = $gallery[0] ?? null;

    $engineCc = $vehicle->engine_cc ?: $vehicle->variant?->engine_cc;
    $power = $vehicle->power_bhp ?: $vehicle->variant?->power_bhp;
    $torque = $vehicle->torque_nm ?: $vehicle->variant?->torque_nm;
    $mileage = $vehicle->mileage_kmpl ?: $vehicle->variant?->mileage_kmpl;
    $seats = $vehicle->seats ?: $vehicle->variant?->seats;

    $quickSpecs = array_values(array_filter([
        ['label' => 'Year', 'value' => (string) $vehicle->year, 'icon' => 'calendar'],
        ['label' => 'Kilometres', 'value' => $vehicle->formatted_km, 'icon' => 'gauge'],
        ['label' => 'Fuel', 'value' => $vehicle->fuelType?->name, 'icon' => 'fuel'],
        ['label' => 'Transmission', 'value' => $vehicle->transmission?->label(), 'icon' => 'cog'],
    ]));

    $specs = array_values(array_filter([
        ['label' => 'Manufacturing year', 'value' => $vehicle->year],
        ['label' => 'Kilometres driven', 'value' => $vehicle->formatted_km],
        ['label' => 'Fuel type', 'value' => $vehicle->fuelType?->name],
        ['label' => 'Transmission', 'value' => $vehicle->transmission?->label()],
        ['label' => 'Ownership', 'value' => $vehicle->owner_label],
        ['label' => 'Seats', 'value' => $seats ? $seats.' seater' : null],
        ['label' => 'Colour', 'value' => $vehicle->colour],
        ['label' => 'Condition', 'value' => $vehicle->condition?->label()],
        ['label' => 'Registration state', 'value' => $vehicle->registration_state],
        ['label' => 'Registration year', 'value' => $vehicle->registration_year],
        ['label' => 'Engine', 'value' => $engineCc ? number_format((int) $engineCc).' cc' : null],
        ['label' => 'Power', 'value' => $power ? rtrim(rtrim(number_format((float) $power, 2), '0'), '.').' bhp' : null],
        ['label' => 'Torque', 'value' => $torque ? number_format((int) $torque).' Nm' : null],
        ['label' => 'Mileage', 'value' => $mileage ? rtrim(rtrim(number_format((float) $mileage, 2), '0'), '.').' kmpl' : null],
    ], fn (array $spec) => $spec['value'] !== null && $spec['value'] !== ''));

    $featureGroups = $vehicle->features
        ->groupBy(fn ($feature) => $feature->category ?: 'comfort');

    $featureGroupLabels = [
        'safety' => 'Safety',
        'comfort' => 'Comfort & convenience',
        'technology' => 'Technology',
        'exterior' => 'Exterior',
        'interior' => 'Interior',
    ];

    $whatsappMessage = rawurlencode('Hi, I am interested in the '.$title.' listed at '.$vehicle->public_url.'. Please share more details.');
@endphp

@section('content')
    <div class="container-page py-6 lg:py-10">
        <x-breadcrumbs :items="$seo->breadcrumbs" />

        <div class="mt-4 flex flex-col gap-4 border-b border-ink-100 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($vehicle->is_featured)
                        <span class="badge-brand">Featured</span>
                    @endif
                    @if ($vehicle->condition)
                        <span class="badge-soft">{{ $vehicle->condition->label() }}</span>
                    @endif
                    @if ($vehicle->has_360)
                        <span class="badge-ink"><x-wd-icon name="rotate" size="xs" /> 360° view</span>
                    @endif
                </div>

                <h1 class="mt-3 text-3xl font-black leading-tight tracking-tight sm:text-4xl lg:text-[2.75rem]">
                    {{ $seo->h1 }}
                </h1>

                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold text-ink-500">
                    <span class="inline-flex items-center gap-1.5">
                        <x-wd-icon name="map-pin" size="xs" />
                        {{ $vehicle->city?->name ?? 'India' }}@if ($vehicle->city?->state) · {{ $vehicle->city->state }}@endif
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-wd-icon name="eye" size="xs" />
                        {{ number_format($vehicle->views_count) }} views
                    </span>
                    @if ($vehicle->published_at)
                        <span class="inline-flex items-center gap-1.5">
                            <x-wd-icon name="clock" size="xs" />
                            Listed {{ $vehicle->published_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-end justify-between gap-4 lg:flex-col lg:items-end">
                <div class="lg:text-right">
                    <p class="text-3xl font-black tracking-tight text-ink sm:text-4xl">{{ format_inr($vehicle->price) }}</p>
                    @if ($vehicle->original_price && $vehicle->original_price > $vehicle->price)
                        <p class="mt-1 text-sm font-semibold text-ink-400">
                            <span class="line-through">{{ format_inr($vehicle->original_price) }}</span>
                            <span class="ml-1 font-black text-emerald-700">
                                Save {{ format_inr($vehicle->original_price - $vehicle->price, true) }}
                            </span>
                        </p>
                    @endif
                    <p class="mt-1 text-xs font-semibold text-ink-400">Insurance and transfer charges extra</p>
                </div>

                <div
                    x-data="{
                        copied: false,
                        async share() {
                            const url = window.location.href;
                            if (navigator.share) {
                                try { await navigator.share({ title: document.title, url }); } catch (error) {}
                                return;
                            }
                            try {
                                await navigator.clipboard.writeText(url);
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2000);
                            } catch (error) {
                                window.prompt('Copy this link', url);
                            }
                        }
                    }"
                >
                    <button type="button" class="btn-outline btn-sm" @click="share()">
                        <x-wd-icon name="document" size="xs" />
                        <span x-text="copied ? 'Link copied' : 'Share'">Share</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_23rem] xl:gap-10">
            <div class="min-w-0">
                <div x-data="{ tab: 'photos' }">
                    @if ($vehicle->has_360)
                        <div class="mb-3 flex gap-1.5" role="tablist" aria-label="Vehicle media">
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="tab === 'photos' ? 'true' : 'false'"
                                :class="tab === 'photos' ? 'chip chip-active' : 'chip'"
                                @click="tab = 'photos'"
                            >
                                <x-wd-icon name="camera" size="xs" /> Photos
                            </button>
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="tab === '360' ? 'true' : 'false'"
                                :class="tab === '360' ? 'chip chip-active' : 'chip'"
                                @click="tab = '360'"
                            >
                                <x-wd-icon name="rotate" size="xs" /> 360° view
                            </button>
                        </div>
                    @endif

                    <div x-show="tab === 'photos'" role="tabpanel" aria-label="Photos">
                        @if ($gallery !== [])
                            <div x-data="vehicleGallery({{ Js::from($gallery) }}, 0)">
                                <div
                                    @keydown.arrow-right.prevent="next()"
                                    @keydown.arrow-left.prevent="previous()"
                                    tabindex="0"
                                    class="group relative overflow-hidden rounded-2xl border border-ink-100 bg-ink-900 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/40"
                                    aria-label="Vehicle photo gallery. Use the left and right arrow keys to change photo."
                                >
                                    <img
                                    src="{{ $primary['url'] }}"
                                    srcset="{{ $primary['srcset'] }}"
                                    sizes="(min-width: 1024px) 65vw, 100vw"
                                    alt="{{ $primary['alt'] }}"
                                    :src="current.url"
                                    :srcset="current.srcset"
                                    :alt="current.alt"
                                    width="{{ $primary['width'] }}"
                                    height="{{ $primary['height'] }}"
                                    loading="eager"
                                    fetchpriority="high"
                                    decoding="async"
                                    class="aspect-[16/10] w-full object-cover"
                                >

                                @if (count($gallery) > 1)
                                    <button
                                        type="button"
                                        class="absolute left-3 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-ink/70 text-white backdrop-blur transition hover:bg-ink"
                                        @click="previous()"
                                        aria-label="Previous photo"
                                    >
                                        <x-wd-icon name="chevron-left" size="sm" />
                                    </button>
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-ink/70 text-white backdrop-blur transition hover:bg-ink"
                                        @click="next()"
                                        aria-label="Next photo"
                                    >
                                        <x-wd-icon name="chevron-right" size="sm" />
                                    </button>

                                    <span class="absolute bottom-3 right-3 rounded-full bg-ink/75 px-2.5 py-1 text-[11px] font-black text-white backdrop-blur">
                                        <span x-text="active + 1">1</span> / {{ count($gallery) }}
                                    </span>
                                @endif
                            </div>

                            @if (count($gallery) > 1)
                                <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-6">
                                    @foreach ($gallery as $index => $image)
                                        <button
                                            type="button"
                                            class="overflow-hidden rounded-lg border-2 transition focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/40"
                                            :class="active === {{ $index }} ? 'border-ink' : 'border-transparent hover:border-ink-300'"
                                            @click="select({{ $index }})"
                                            :aria-current="active === {{ $index }} ? 'true' : 'false'"
                                            aria-label="Show photo {{ $index + 1 }} of {{ count($gallery) }}"
                                        >
                                            <img
                                                src="{{ $image['thumb'] }}"
                                                @if ($image['thumb_srcset']) srcset="{{ $image['thumb_srcset'] }}" sizes="(min-width: 640px) 15vw, 22vw" @endif
                                                alt="{{ $image['alt'] }}"
                                                width="350"
                                                height="220"
                                                loading="lazy"
                                                decoding="async"
                                                class="aspect-[16/10] w-full object-cover"
                                            >
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            </div>
                        @else
                            <div class="flex aspect-[16/10] items-center justify-center rounded-2xl border border-ink-100 bg-ink-50 text-ink-300">
                                <div class="text-center">
                                    <x-wd-icon name="car" class="mx-auto h-16 w-16" />
                                    <p class="mt-3 text-sm font-bold">Photos coming soon</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($vehicle->has_360)
                        <div x-show="tab === '360'" x-cloak role="tabpanel" aria-label="360 degree view">
                            <div
                                data-360-viewer
                                data-frames="{{ json_encode($frames, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                data-autoplay="true"
                                tabindex="0"
                                aria-busy="true"
                                class="relative aspect-[16/10] cursor-grab touch-none select-none overflow-hidden rounded-2xl border border-ink-100 bg-ink text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/40"
                                aria-label="360 degree view of {{ $title }}. Drag left or right, or use the arrow keys, to rotate the car."
                            >
                                <img
                                    data-360-image
                                    src="{{ $frames[0] }}"
                                    alt="{{ $title }} — 360 degree exterior view"
                                    width="1200"
                                    height="800"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-full w-full object-cover"
                                >
                                <canvas
                                    data-360-canvas
                                    width="1200"
                                    height="800"
                                    aria-hidden="true"
                                    class="pointer-events-none absolute inset-0 h-full w-full opacity-0 transition-opacity duration-200"
                                ></canvas>

                                <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex items-start justify-end gap-3 bg-gradient-to-b from-ink/70 to-transparent p-4">
                                    <button
                                        type="button"
                                        data-360-toggle
                                        disabled
                                        class="pointer-events-auto rounded-full bg-ink/75 px-3 py-1.5 text-[11px] font-black uppercase tracking-wide text-white backdrop-blur transition hover:bg-ink disabled:cursor-wait disabled:opacity-60"
                                        aria-pressed="true"
                                        aria-label="Pause auto-rotation"
                                    >
                                        Pause
                                    </button>
                                </div>

                                <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-ink/80 to-transparent p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            data-360-prev
                                            disabled
                                            class="pointer-events-auto hidden h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink/70 text-white backdrop-blur transition hover:bg-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/40 disabled:cursor-wait disabled:opacity-40 md:inline-flex"
                                            aria-label="Rotate left"
                                        >
                                            <x-wd-icon name="chevron-left" size="sm" />
                                        </button>

                                        <div class="flex min-w-0 flex-1 flex-col items-center justify-center gap-0.5 text-center md:flex-row md:gap-3">
                                            <span class="inline-flex items-center gap-2 text-xs font-bold text-ink-100">
                                                <x-wd-icon name="rotate" size="xs" class="text-brand-500" />
                                                Drag to rotate · {{ count($frames) }} frames
                                            </span>
                                            <span class="text-[10px] font-bold uppercase tracking-wide text-ink-300 md:hidden">Swipe on mobile</span>
                                        </div>

                                        <button
                                            type="button"
                                            data-360-next
                                            disabled
                                            class="pointer-events-auto hidden h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink/70 text-white backdrop-blur transition hover:bg-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/40 disabled:cursor-wait disabled:opacity-40 md:inline-flex"
                                            aria-label="Rotate right"
                                        >
                                            <x-wd-icon name="chevron-right" size="sm" />
                                        </button>
                                    </div>
                                </div>

                                <div
                                    data-360-loading
                                    class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-4 bg-ink/95 px-6 text-center transition-opacity duration-300"
                                    aria-hidden="false"
                                >
                                    <span
                                        data-360-spinner
                                        class="inline-flex h-12 w-12 animate-spin rounded-full border-4 border-white/20 border-t-brand-500"
                                        aria-hidden="true"
                                    ></span>

                                    <div>
                                        <p data-360-loading-title class="text-sm font-black uppercase tracking-[0.16em] text-white">
                                            Loading 360° view
                                        </p>
                                        <p data-360-status class="mt-1.5 text-xs font-semibold text-ink-200" role="status" aria-live="polite">
                                            Preparing {{ count($frames) }} frames…
                                        </p>
                                    </div>

                                    <div
                                        data-360-progress-wrap
                                        role="progressbar"
                                        aria-label="360 degree frame loading progress"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="0"
                                        class="h-1.5 w-56 max-w-full overflow-hidden rounded-full bg-white/20"
                                    >
                                        <div data-360-progress class="h-full w-0 rounded-full bg-brand-500 transition-[width] duration-300"></div>
                                    </div>

                                    <div data-360-error class="hidden max-w-xs">
                                        <p class="text-sm font-bold text-white">Some 360° frames could not load.</p>
                                        <button
                                            type="button"
                                            data-360-retry
                                            class="mt-3 inline-flex rounded-full bg-brand-500 px-4 py-2 text-xs font-black uppercase tracking-wide text-ink transition hover:bg-brand-400"
                                        >
                                            Try again
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($quickSpecs as $spec)
                        <div class="rounded-xl border border-ink-100 bg-white p-3.5">
                            <span class="text-ink-400"><x-wd-icon :name="$spec['icon']" size="sm" /></span>
                            <p class="mt-2 text-[10px] font-black uppercase tracking-[0.14em] text-ink-400">{{ $spec['label'] }}</p>
                            <p class="mt-0.5 text-sm font-black text-ink">{{ $spec['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <section class="mt-10" aria-labelledby="overview-heading">
                    <h2 id="overview-heading" class="text-2xl font-black tracking-tight">Overview</h2>
                    <div class="prose-wheeldeal mt-4">
                        @if ($vehicle->description)
                            <p>{!! nl2br(e($vehicle->description)) !!}</p>
                        @else
                            <p>
                                This {{ $vehicle->year }} {{ $title }} is listed in {{ $vehicle->city?->name ?? 'India' }}
                                with {{ $vehicle->formatted_km }} on the odometer. It runs on {{ $vehicle->fuelType?->name ?? 'fuel' }}
                                with a {{ $vehicle->transmission?->label() ?? 'manual' }} gearbox and is offered as a
                                {{ strtolower($vehicle->owner_label) }} vehicle. Contact our team for the full inspection notes,
                                service history and to arrange a viewing.
                            </p>
                        @endif

                        <p>
                            Every enquiry is handled by a real person. We will confirm availability, answer questions about the
                            car and help you arrange an inspection before you commit.
                        </p>
                    </div>
                </section>

                <section class="mt-10" aria-labelledby="specs-heading">
                    <h2 id="specs-heading" class="text-2xl font-black tracking-tight">Specifications</h2>
                    <dl class="mt-4 grid gap-x-8 gap-y-0 sm:grid-cols-2">
                        @foreach ($specs as $spec)
                            <div class="flex items-baseline justify-between gap-4 border-b border-ink-100 py-3">
                                <dt class="text-xs font-bold uppercase tracking-wide text-ink-400">{{ $spec['label'] }}</dt>
                                <dd class="text-right text-sm font-black text-ink">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                @if ($featureGroups->isNotEmpty())
                    <section class="mt-10" aria-labelledby="features-heading">
                        <h2 id="features-heading" class="text-2xl font-black tracking-tight">Features</h2>

                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            @foreach ($featureGroups as $group => $features)
                                <div class="rounded-2xl border border-ink-100 bg-white p-5">
                                    <h3 class="text-xs font-black uppercase tracking-[0.14em] text-ink-500">
                                        {{ $featureGroupLabels[$group] ?? \Illuminate\Support\Str::headline($group) }}
                                    </h3>
                                    <ul class="mt-3 space-y-2.5">
                                        @foreach ($features as $feature)
                                            <li class="flex items-start gap-2 text-sm font-semibold text-ink-700">
                                                <x-wd-icon name="check" size="xs" class="mt-0.5 text-emerald-600" />
                                                {{ $feature->name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mt-10 rounded-2xl border border-ink-100 bg-sand p-6 sm:p-8" aria-labelledby="buying-heading">
                    <h2 id="buying-heading" class="text-xl font-black tracking-tight">Buying this car</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-3">
                        @foreach ([
                            ['search', 'Enquire', 'Tell us you are interested and we will confirm availability.'],
                            ['eye', 'Inspect', 'See the car in person, check the documents and take a test drive.'],
                            ['key', 'Decide', 'Agree the price and transfer with a clear, written process.'],
                        ] as [$icon, $stepTitle, $copy])
                            <div>
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-brand-500">
                                    <x-wd-icon :name="$icon" size="xs" />
                                </span>
                                <p class="mt-3 text-sm font-black text-ink">{{ $stepTitle }}</p>
                                <p class="mt-1 text-xs leading-5 text-ink-500">{{ $copy }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="min-w-0" aria-label="Enquire about this vehicle">
                <div id="enquire" class="card p-5 lg:sticky lg:top-24 lg:p-6">
                    <div class="flex items-start justify-between gap-3 border-b border-ink-100 pb-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-ink-400">Asking price</p>
                            <p class="mt-1 text-2xl font-black tracking-tight text-ink">{{ format_inr($vehicle->price) }}</p>
                            <p class="mt-1 text-xs font-semibold text-ink-500">{{ $vehicle->owner_label }} · {{ $vehicle->formatted_km }}</p>
                        </div>
                        <span class="badge-success"><x-wd-icon name="shield-check" size="xs" /> Verified</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="btn-outline btn-sm">
                            <x-wd-icon name="phone" size="xs" /> Call
                        </a>
                        <a href="https://wa.me/{{ config('marketplace.brand.whatsapp') }}?text={{ $whatsappMessage }}" target="_blank" rel="noopener noreferrer" class="btn-secondary btn-sm">
                            <x-wd-icon name="whatsapp" size="xs" /> WhatsApp
                        </a>
                    </div>

                    <div class="mt-5 border-t border-ink-100 pt-5">
                        <h2 class="text-base font-black text-ink">Send an enquiry</h2>
                        <p class="mt-1 text-xs leading-5 text-ink-500">
                            No account needed. We only use your number to respond to this enquiry.
                        </p>

                        <div class="mt-4">
                            <x-enquiry-form :vehicle="$vehicle" />
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-14 border-t border-ink-100 pt-10" aria-labelledby="similar-heading">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Keep comparing</p>
                        <h2 id="similar-heading" class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Similar cars you may like</h2>
                    </div>
                    <a href="{{ route('categories.show', $vehicle->category?->slug ?? 'suv') }}" class="link inline-flex items-center gap-1.5 text-sm">
                        More {{ $vehicle->category?->name ?? 'cars' }} <x-wd-icon name="arrow-right" size="xs" />
                    </a>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similar as $related)
                        <x-vehicle-card :vehicle="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-100 bg-white/95 p-3 shadow-[0_-8px_30px_rgba(17,17,17,0.08)] backdrop-blur lg:hidden">
        <div class="container-page flex items-center gap-3 px-0">
            <div class="min-w-0 flex-1">
                <p class="truncate text-[11px] font-bold uppercase tracking-wide text-ink-400">{{ $vehicle->owner_label }}</p>
                <p class="text-lg font-black leading-tight text-ink">{{ format_inr($vehicle->price) }}</p>
            </div>
            <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="btn-outline btn-sm" aria-label="Call about this car">
                <x-wd-icon name="phone" size="xs" />
            </a>
            <a href="#enquire" class="btn-primary btn-sm">
                Enquire now
                <x-wd-icon name="arrow-right" size="xs" />
            </a>
        </div>
    </div>
@endsection

@section('body-class', 'pb-20 lg:pb-0')

@if ($vehicle->has_360)
    @push('scripts')
        @vite('resources/js/vehicle-360.js')
    @endpush
@endif
