@props(['items' => []])

@if (count($items) > 1)
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-ink-500">
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    @if (! $loop->last)
                        <a href="{{ $item['url'] }}" class="transition hover:text-ink">{{ $item['label'] }}</a>
                        <x-wd-icon name="chevron-right" size="xs" class="text-ink-300" />
                    @else
                        <span class="text-ink" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
