@props(['vehicle', 'eager' => false])

@php
    $title = $vehicle->full_name ?: $vehicle->title;
    $image = $vehicle->primary_image_url;
    $imageAlt = $vehicle->primary_image_alt;
    $imageSrcset = $vehicle->primary_image_srcset;
@endphp

<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card transition duration-200 hover:-translate-y-0.5 hover:shadow-lift focus-within:ring-4 focus-within:ring-brand-500/30">
    <a
        href="{{ $vehicle->public_url }}"
        class="flex h-full flex-col"
    >
        <div class="relative aspect-[16/10] overflow-hidden bg-ink-900">
            @if ($image)
                <img
                    src="{{ $image }}"
                    @if ($imageSrcset) srcset="{{ $imageSrcset }}" sizes="(min-width: 1280px) 30vw, (min-width: 640px) 45vw, 92vw" @endif
                    alt="{{ $imageAlt }}"
                    width="700"
                    height="440"
                    @if ($eager) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                    decoding="async"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                >
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-ink-800 to-ink-900 text-brand-500">
                    <x-wd-icon name="car" size="xl" />
                </div>
            @endif

            <div class="absolute inset-x-3 top-3 flex flex-wrap items-start justify-between gap-2">
                <div class="flex flex-wrap gap-1.5">
                    @if ($vehicle->is_featured)
                        <span class="badge-brand">Featured</span>
                    @endif
                    @if ($vehicle->has_360)
                        <span class="badge bg-ink/85 text-white backdrop-blur">
                            <x-wd-icon name="rotate" size="xs" /> 360&deg;
                        </span>
                    @endif
                </div>

                @if ($vehicle->condition)
                    <span class="badge bg-white/90 text-ink backdrop-blur">{{ $vehicle->condition->label() }}</span>
                @endif
            </div>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <h3 class="line-clamp-2 text-base font-black leading-snug text-ink">{{ $title }}</h3>
                <x-wd-icon name="chevron-right" size="sm" class="mt-0.5 text-ink-300 transition group-hover:translate-x-0.5 group-hover:text-ink" />
            </div>

            <p class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-ink-500">
                <x-wd-icon name="map-pin" size="xs" />
                {{ $vehicle->city?->name ?? 'India' }}@if ($vehicle->city?->state) · {{ $vehicle->city->state }}@endif
            </p>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-bold text-ink-600">
                <span class="inline-flex items-center gap-1.5">
                    <x-wd-icon name="calendar" size="xs" class="text-ink-400" /> {{ $vehicle->year }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <x-wd-icon name="fuel" size="xs" class="text-ink-400" /> {{ $vehicle->fuelType?->name ?? 'Fuel' }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <x-wd-icon name="gauge" size="xs" class="text-ink-400" /> {{ $vehicle->formatted_km }}
                </span>
            </div>

            <div class="mt-auto flex items-end justify-between gap-3 pt-5">
                <div>
                    <p class="text-xl font-black tracking-tight text-ink">{{ format_inr($vehicle->price) }}</p>
                    @if ($vehicle->original_price && $vehicle->original_price > $vehicle->price)
                        <p class="mt-0.5 text-xs font-semibold text-ink-400 line-through">{{ format_inr($vehicle->original_price) }}</p>
                    @endif
                </div>
                <span class="text-right text-[11px] font-bold uppercase tracking-wide text-ink-400">{{ $vehicle->owner_label }}</span>
            </div>
        </div>
    </a>
</article>
