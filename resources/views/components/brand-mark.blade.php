@props(['class' => 'h-10 w-10'])

<span
    {{ $attributes->merge([
        'class' => 'inline-flex shrink-0 items-center justify-center rounded-xl bg-ink text-brand-500 '.$class,
    ]) }}
    aria-hidden="true"
>
    <svg viewBox="0 0 32 32" fill="none" class="h-3/5 w-3/5">
        <circle cx="16" cy="16" r="10" stroke="currentColor" stroke-width="2.5" />
        <circle cx="16" cy="16" r="2.75" fill="currentColor" />
        <path d="M16 6v7.25M16 18.75V26M6 16h7.25M18.75 16H26M9 9l5.1 5.1M17.9 17.9L23 23M23 9l-5.1 5.1M14.1 17.9L9 23" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
</span>
