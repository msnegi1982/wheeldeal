@extends('layouts.app')

@section('title', 'Page not found — '.config('seo.site_name'))

@push('head')
    <meta name="robots" content="noindex,follow">
@endpush

@section('content')
    <div class="container-page py-14 lg:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <p class="font-display text-7xl font-black tracking-tighter text-ink-200 sm:text-8xl">404</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">That road ends here.</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-7 text-ink-600">
                The page may have moved, the car may have sold, or the link may be wrong. Try the catalogue or search for the car you had in mind.
            </p>

            <form action="{{ route('vehicles.index') }}" method="GET" class="mx-auto mt-7 flex max-w-lg flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                    <label for="not-found-search" class="sr-only">Search cars</label>
                    <x-wd-icon name="search" size="sm" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400" />
                    <input type="search" id="not-found-search" name="q" placeholder="Search brand or model" class="input pl-10">
                </div>
                <button type="submit" class="btn-primary">
                    Search cars
                    <x-wd-icon name="arrow-right" size="sm" />
                </button>
            </form>

            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="{{ route('home') }}" class="chip"><x-wd-icon name="home" size="xs" /> Home</a>
                <a href="{{ route('vehicles.index') }}" class="chip"><x-wd-icon name="car" size="xs" /> All used cars</a>
                <a href="{{ route('sell.create') }}" class="chip"><x-wd-icon name="tag" size="xs" /> Sell your car</a>
                <a href="{{ route('contact') }}" class="chip"><x-wd-icon name="mail" size="xs" /> Contact us</a>
            </div>

            <p class="mt-8 text-xs leading-5 text-ink-500">
                Looking for a specific listing? Send us the name or reference number and we will point you in the right direction.
            </p>
        </div>
    </div>
@endsection
