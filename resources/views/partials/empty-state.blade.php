@php
    $title = $title ?? 'Nothing here yet';
    $message = $message ?? 'Try adjusting your filters or browse the full catalogue.';
    $actionLabel = $actionLabel ?? 'Browse all cars';
    $actionUrl = $actionUrl ?? null;
@endphp

<div class="rounded-2xl border border-dashed border-ink-200 bg-ink-50 px-6 py-14 text-center">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-ink-400 shadow-sm">
        <x-wd-icon name="search" size="lg" />
    </span>
    <h3 class="mt-5 text-lg font-black text-ink">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-500">{{ $message }}</p>

    @if ($actionUrl)
        <a href="{{ $actionUrl }}" class="btn-primary mt-6">
            {{ $actionLabel }}
            <x-wd-icon name="arrow-right" size="sm" />
        </a>
    @endif
</div>
