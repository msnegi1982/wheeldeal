@php
    $footerBrands = \Illuminate\Support\Facades\Cache::remember('footer:brands', 3600, fn () => \App\Models\Brand::query()
        ->active()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->limit(6)
        ->get(['name', 'slug']));

    $footerCategories = \Illuminate\Support\Facades\Cache::remember('footer:categories', 3600, fn () => \App\Models\Category::query()
        ->active()
        ->orderBy('sort_order')
        ->limit(5)
        ->get(['name', 'slug']));

    $socials = array_filter([
        'Facebook' => config('marketplace.social.facebook'),
        'Instagram' => config('marketplace.social.instagram'),
        'YouTube' => config('marketplace.social.youtube'),
        'X' => config('marketplace.social.x'),
    ]);
@endphp

<footer class="bg-ink text-white">
    <div class="container-page py-14 lg:py-18">
        <div class="grid gap-10 lg:grid-cols-[1.5fr_1fr_1fr_1.25fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="{{ config('seo.site_name') }} home">
                    <x-brand-mark class="h-11 w-11" />
                    <span class="font-display text-xl font-black tracking-tight text-white">{{ config('seo.site_name') }}</span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-6 text-ink-300">
                    {{ config('marketplace.brand.tagline') }} Verified listings, transparent pricing and a real person on the other end of the phone.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-ink-200">
                        <x-wd-icon name="shield-check" size="xs" class="text-brand-500" /> Verified
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-ink-200">
                        <x-wd-icon name="rotate" size="xs" class="text-brand-500" /> 360° views
                    </span>
                </div>

                @if ($socials !== [])
                    <div class="mt-5 flex flex-wrap gap-3">
                        @foreach ($socials as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-ink-300 underline decoration-white/20 underline-offset-4 hover:text-white">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-brand-500">Explore</h2>
                <ul class="mt-4 space-y-3 text-sm font-semibold text-ink-200">
                    <li><a href="{{ route('vehicles.index') }}" class="footer-link">All used cars</a></li>
                    @foreach ($footerCategories as $category)
                        <li><a href="{{ route('categories.show', $category->slug) }}" class="footer-link">Used {{ $category->name }} cars</a></li>
                    @endforeach
                    @foreach ($footerBrands as $brand)
                        <li><a href="{{ route('brands.show', $brand->slug) }}" class="footer-link">Used {{ $brand->name }} cars</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-brand-500">Company</h2>
                <ul class="mt-4 space-y-3 text-sm font-semibold text-ink-200">
                    <li><a href="{{ route('pages.about') }}" class="footer-link">About us</a></li>
                    <li><a href="{{ route('contact') }}" class="footer-link">Contact</a></li>
                    <li><a href="{{ route('sell.create') }}" class="footer-link">Sell your car</a></li>
                    <li><a href="{{ route('buy.create') }}" class="footer-link">Tell us what to find</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="footer-link">Privacy policy</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="footer-link">Terms of use</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-brand-500">Talk to us</h2>
                <ul class="mt-4 space-y-4 text-sm font-semibold text-ink-200">
                    <li>
                        <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="flex items-start gap-3 hover:text-white">
                            <x-wd-icon name="phone" size="sm" class="mt-0.5 text-brand-500" />
                            <span>{{ config('marketplace.brand.phone') }}<span class="block text-xs font-medium text-ink-300">{{ config('marketplace.brand.hours') }}</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="https://wa.me/{{ config('marketplace.brand.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="flex items-start gap-3 hover:text-white">
                            <x-wd-icon name="whatsapp" size="sm" class="mt-0.5 text-brand-500" />
                            <span>WhatsApp us</span>
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ config('marketplace.brand.email') }}" class="flex items-start gap-3 hover:text-white">
                            <x-wd-icon name="mail" size="sm" class="mt-0.5 text-brand-500" />
                            <span>{{ config('marketplace.brand.email') }}</span>
                        </a>
                    </li>
                    <li class="flex items-start gap-3">
                        <x-wd-icon name="map-pin" size="sm" class="mt-0.5 text-brand-500" />
                        <span class="leading-6">{{ config('marketplace.brand.address') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-ink-300 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ config('seo.site_name') }}. All rights reserved.</p>
            <p>Prices in INR. Vehicle availability is subject to inspection.</p>
        </div>
    </div>
</footer>
