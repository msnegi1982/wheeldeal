@php
    $total = $vehicles->total();
@endphp

@include('vehicles.partials.active-filters', [
    'filters' => $filters,
    'options' => $options,
])

@if ($vehicles->isEmpty())
    @include('partials.empty-state', [
        'title' => 'No cars match those filters',
        'message' => 'Try widening your budget, removing a filter or searching a nearby city. New stock is added every week.',
        'actionLabel' => 'Clear all filters',
        'actionUrl' => request()->routeIs('categories.show', 'catalog.category-brand', 'brands.show')
            ? request()->url()
            : route('vehicles.index'),
    ])
@else
    <div class="grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
        @foreach ($vehicles as $vehicle)
            <x-vehicle-card :vehicle="$vehicle" :eager="$loop->first" />
        @endforeach
    </div>

    <p class="mt-6 text-xs font-semibold text-ink-500">
        Showing {{ number_format($vehicles->firstItem()) }}–{{ number_format($vehicles->lastItem()) }}
        of {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('car', $total) }}
    </p>

    <div class="mt-6">
        {{ $vehicles->links('partials.pagination') }}
    </div>
@endif
