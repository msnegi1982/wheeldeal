<header
    class="sticky top-0 z-40 border-b border-ink-100 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
>
    <div class="container-page flex h-18 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ config('seo.site_name') }} home">
            <x-brand-mark class="h-10 w-10" />
            <span class="flex flex-col leading-none">
                <span class="font-display text-lg font-black tracking-tight text-ink">{{ config('seo.site_name') }}</span>
                <span class="mt-1 hidden text-[10px] font-bold uppercase tracking-[0.16em] text-ink-400 sm:block">Verified used cars</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary">
            <a
                href="{{ route('vehicles.index') }}"
                @class([
                    'nav-link',
                    'nav-link-active' => request()->routeIs('vehicles.*', 'categories.*', 'catalog.*', 'brands.*'),
                ])
                @if (request()->routeIs('vehicles.*', 'categories.*', 'catalog.*', 'brands.*')) aria-current="page" @endif
            >
                Buy cars
            </a>
            <a href="{{ route('sell.create') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('sell.*', 'buy.*', 'requests.*')])>
                Sell your car
            </a>
            <a href="{{ route('home') }}#how-it-works" class="nav-link">How it works</a>
            <a href="{{ route('pages.about') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('pages.about')])>About</a>
            <a href="{{ route('contact') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('contact')])>Contact</a>
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-extrabold text-ink hover:bg-ink-50">
                <x-wd-icon name="phone" size="sm" class="text-ink-400" />
                <span>{{ config('marketplace.brand.phone') }}</span>
            </a>
            <a href="{{ route('sell.create') }}" class="btn-primary">
                Sell your car
                <x-wd-icon name="arrow-right" size="sm" />
            </a>
        </div>

        <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-ink-200 text-ink transition hover:border-ink hover:bg-ink-50 lg:hidden"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-controls="mobile-navigation"
            aria-label="Toggle navigation menu"
        >
            <x-wd-icon name="menu" x-show="!open" />
            <x-wd-icon name="close" x-show="open" x-cloak />
        </button>
    </div>

    <div
        id="mobile-navigation"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="border-t border-ink-100 bg-white lg:hidden"
    >
        <nav class="container-page flex flex-col gap-1 py-4" aria-label="Mobile primary">
            <a href="{{ route('vehicles.index') }}" class="mobile-nav-link" @click="open = false">
                <x-wd-icon name="car" size="sm" class="text-ink-400" /> Buy cars
            </a>
            <a href="{{ route('sell.create') }}" class="mobile-nav-link" @click="open = false">
                <x-wd-icon name="tag" size="sm" class="text-ink-400" /> Sell your car
            </a>
            <a href="{{ route('buy.create') }}" class="mobile-nav-link" @click="open = false">
                <x-wd-icon name="search" size="sm" class="text-ink-400" /> Tell us what to find
            </a>
            <a href="{{ route('home') }}#how-it-works" class="mobile-nav-link" @click="open = false">
                <x-wd-icon name="info" size="sm" class="text-ink-400" /> How it works
            </a>
            <a href="{{ route('pages.about') }}" class="mobile-nav-link" @click="open = false">
                <x-wd-icon name="users" size="sm" class="text-ink-400" /> About us
            </a>
            <a href="{{ route('contact') }}" class="mobile-nav-link" @click="open = false">
                <x-wd-icon name="mail" size="sm" class="text-ink-400" /> Contact
            </a>

            <div class="mt-3 grid grid-cols-2 gap-2 border-t border-ink-100 pt-4">
                <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="btn-outline">
                    <x-wd-icon name="phone" size="sm" /> Call us
                </a>
                <a href="{{ route('sell.create') }}" class="btn-primary" @click="open = false">
                    Sell your car
                </a>
            </div>
        </nav>
    </div>
</header>
