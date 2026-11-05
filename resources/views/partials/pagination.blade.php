@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-4 border-t border-ink-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs font-semibold text-ink-500">
            Showing <span class="text-ink">{{ $paginator->firstItem() }}</span>–<span class="text-ink">{{ $paginator->lastItem() }}</span>
            of <span class="text-ink">{{ number_format($paginator->total()) }}</span> vehicles
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-ink-100 px-2 text-ink-300" aria-disabled="true" aria-label="Previous page">
                    <x-wd-icon name="chevron-left" size="sm" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-ink-200 px-2 text-ink transition hover:border-ink hover:bg-ink-50" aria-label="Previous page">
                    <x-wd-icon name="chevron-left" size="sm" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-10 min-w-10 items-center justify-center px-1 text-sm font-bold text-ink-400" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg bg-ink px-2 text-sm font-black text-white" aria-current="page" aria-label="Page {{ $page }}, current page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-ink-200 px-2 text-sm font-bold text-ink transition hover:border-ink hover:bg-ink-50" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-ink-200 px-2 text-ink transition hover:border-ink hover:bg-ink-50" aria-label="Next page">
                    <x-wd-icon name="chevron-right" size="sm" />
                </a>
            @else
                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-ink-100 px-2 text-ink-300" aria-disabled="true" aria-label="Next page">
                    <x-wd-icon name="chevron-right" size="sm" />
                </span>
            @endif
        </div>
    </nav>
@endif
