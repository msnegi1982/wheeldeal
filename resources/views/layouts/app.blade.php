@php($pageSeo = $seo ?? null)

<!DOCTYPE html>
<html lang="en-IN" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FFC107">
    <meta name="color-scheme" content="light">

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23111'/%3E%3Ccircle cx='16' cy='16' r='9' fill='none' stroke='%23FFC107' stroke-width='3'/%3E%3Ccircle cx='16' cy='16' r='2.5' fill='%23FFC107'/%3E%3C/svg%3E">

    <title>@yield('title', $pageSeo?->title ?? config('seo.default_title'))</title>

    @if ($pageSeo)
        @include('partials.seo', ['seo' => $pageSeo])
    @else
        <meta name="description" content="{{ config('seo.default_description') }}">
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-ink antialiased @yield('body-class')">
    <a href="#main-content" class="sr-only z-[100] rounded-lg bg-brand-500 px-4 py-3 text-sm font-black text-ink focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Skip to main content
    </a>

    <x-site-header />

    @include('partials.alerts')

    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    <x-site-footer />

    @stack('scripts')
</body>
</html>
