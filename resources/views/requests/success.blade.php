@extends('layouts.app')

@php
    $isSell = $customerRequest->type === \App\Enums\RequestType::SELL;
    $reference = strtoupper(substr($customerRequest->uuid, 0, 8));
@endphp

@section('content')
    <div class="bg-sand">
        <div class="container-page py-10 lg:py-16">
            <div class="mx-auto max-w-3xl">
                <x-breadcrumbs :items="$seo->breadcrumbs" />

                <div class="mt-6 overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-card">
                    <div class="px-6 py-8 text-center sm:px-10 sm:py-10">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                            <x-wd-icon name="check" size="lg" />
                        </span>

                        <p class="mt-5 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">Verified successfully</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Your request is in.</h1>
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-ink-600">
                            Thanks, {{ $customerRequest->name }}. Your mobile number is verified and your
                            {{ $isSell ? 'sell' : 'buy' }} request has been passed to our team. We will contact you on
                            {{ $customerRequest->phone }} during business hours.
                        </p>

                        <div class="mx-auto mt-6 inline-flex flex-wrap items-center justify-center gap-2 rounded-xl bg-ink-50 px-4 py-3">
                            <span class="text-[10px] font-black uppercase tracking-wide text-ink-400">Reference</span>
                            <span class="font-mono text-sm font-black tracking-wider text-ink">{{ $reference }}</span>
                            <span class="badge-soft">{{ $customerRequest->status->label() }}</span>
                        </div>
                    </div>

                    <div class="grid gap-0 border-t border-ink-100 sm:grid-cols-[1.1fr_0.9fr]">
                        <div class="p-6 sm:p-8">
                            <h2 class="text-sm font-black uppercase tracking-wide text-ink-500">What happens next</h2>

                            <ol class="mt-4 space-y-5">
                                @foreach ([
                                    ['clock', 'We review the details', 'Our team checks the request and matches it with the right person.'],
                                    ['phone', 'We call you', $isSell ? 'We discuss the car, your price expectation and the next practical steps.' : 'We shortlist cars that fit and talk through availability, viewings and budgets.'],
                                    ['key', 'You decide', 'No pressure and no obligation. We only move forward when you are ready.'],
                                ] as $index => [$icon, $stepTitle, $copy])
                                    <li class="flex gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-ink">
                                            <x-wd-icon :name="$icon" size="xs" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-black text-ink">{{ $index + 1 }}. {{ $stepTitle }}</p>
                                            <p class="mt-1 text-xs leading-5 text-ink-500">{{ $copy }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>

                            <div class="mt-7 flex flex-col gap-2 sm:flex-row">
                                <a href="{{ route('vehicles.index') }}" class="btn-primary">
                                    Browse cars
                                    <x-wd-icon name="arrow-right" size="sm" />
                                </a>
                                <a href="{{ route('contact') }}" class="btn-outline">
                                    Contact the team
                                </a>
                            </div>
                        </div>

                        <aside class="border-t border-ink-100 bg-ink-50 p-6 sm:border-l sm:border-t-0 sm:p-8" aria-label="Request summary">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-ink-400">Your request</p>

                            <dl class="mt-4 space-y-4">
                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-wide text-ink-400">Request type</dt>
                                    <dd class="mt-0.5 text-sm font-black text-ink">{{ $customerRequest->type->label() }}</dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-wide text-ink-400">Vehicle</dt>
                                    <dd class="mt-0.5 text-sm font-black text-ink">
                                        {{ $customerRequest->brand?->name ?? ($isSell ? 'Your car' : 'Any brand') }}
                                        {{ $customerRequest->vehicleModel?->name }}
                                    </dd>
                                    @if ($customerRequest->year)
                                        <dd class="text-xs font-semibold text-ink-500">{{ $customerRequest->year }} · {{ $customerRequest->km_driven ? number_format($customerRequest->km_driven).' km' : 'Kilometres not specified' }}</dd>
                                    @endif
                                </div>

                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-wide text-ink-400">City</dt>
                                    <dd class="mt-0.5 text-sm font-bold text-ink">{{ $customerRequest->city?->name ?? 'Not specified' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-wide text-ink-400">
                                        {{ $isSell ? 'Expected price' : 'Budget' }}
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-bold text-ink">{{ $customerRequest->formatted_expected_price }}</dd>
                                </div>
                            </dl>

                            <div class="mt-6 rounded-xl border border-ink-200 bg-white p-3">
                                <p class="text-[11px] leading-5 text-ink-600">
                                    Keep your reference number handy if you call us about this request.
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="card flex items-center gap-3 p-4 transition hover:border-ink">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500 text-ink">
                            <x-wd-icon name="phone" size="sm" />
                        </span>
                        <span>
                            <span class="block text-sm font-black text-ink">Call us</span>
                            <span class="block text-xs font-semibold text-ink-500">{{ config('marketplace.brand.phone') }}</span>
                        </span>
                    </a>

                    <a href="https://wa.me/{{ config('marketplace.brand.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="card flex items-center gap-3 p-4 transition hover:border-ink">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ink text-brand-500">
                            <x-wd-icon name="whatsapp" size="sm" />
                        </span>
                        <span>
                            <span class="block text-sm font-black text-ink">WhatsApp</span>
                            <span class="block text-xs font-semibold text-ink-500">{{ config('marketplace.brand.hours') }}</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
