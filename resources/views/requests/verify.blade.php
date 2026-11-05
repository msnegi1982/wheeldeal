@extends('layouts.app')

@php
    $isSell = $customerRequest->type === \App\Enums\RequestType::SELL;
    $channel = $otp?->channel?->label() ?? 'SMS';
@endphp

@section('content')
    <div class="bg-sand">
        <div class="container-page py-10 lg:py-16">
            <div class="mx-auto max-w-3xl">
                <x-breadcrumbs :items="$seo->breadcrumbs" />

                <div class="mt-6 overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-card">
                    <div class="border-b border-ink-100 bg-ink px-6 py-7 text-white sm:px-8">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-ink">
                                <x-wd-icon name="shield-check" size="sm" />
                            </span>
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.16em] text-brand-500">Step 2 of 3</p>
                                <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl">Verify your mobile number</h1>
                            </div>
                        </div>

                        <p class="mt-4 max-w-xl text-sm leading-6 text-ink-200">
                            We sent a 6-digit code by {{ $channel }} to
                            <span class="font-black text-white">{{ $maskedPhone }}</span>.
                            Enter it below to confirm your {{ $isSell ? 'sell' : 'buy' }} request.
                        </p>
                    </div>

                    <div class="grid gap-0 sm:grid-cols-[1.1fr_0.9fr]">
                        <div class="p-6 sm:p-8">
                            <form method="POST" action="{{ route('requests.verify.submit', $customerRequest) }}" class="space-y-5">
                                @csrf

                                <div>
                                    <label for="otp-code" class="label">6-digit verification code</label>
                                    <input
                                        type="text"
                                        id="otp-code"
                                        name="code"
                                        value="{{ old('code') }}"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        pattern="[0-9]{6}"
                                        maxlength="6"
                                        required
                                        autofocus
                                        data-otp-input
                                        placeholder="000000"
                                        class="input text-center text-2xl font-black tracking-[0.55em] @error('code') border-red-500 @enderror"
                                        @error('code') aria-invalid="true" aria-describedby="otp-code-error" @enderror
                                    >
                                    @error('code')
                                        <p id="otp-code-error" class="field-error" role="alert">{{ $message }}</p>
                                    @enderror
                                    <p class="help">The code expires five minutes after it is sent.</p>
                                </div>

                                <button type="submit" class="btn-primary w-full">
                                    Verify and continue
                                    <x-wd-icon name="arrow-right" size="sm" />
                                </button>
                            </form>

                            <div class="mt-6 border-t border-ink-100 pt-5" x-data="resendCountdown({{ $resendAfter }})">
                                <p class="text-xs leading-5 text-ink-500">Didn't get the code? Check your messages, then request another.</p>

                                <form method="POST" action="{{ route('requests.resend', $customerRequest) }}" class="mt-3">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="btn-outline btn-sm w-full sm:w-auto"
                                        :disabled="seconds > 0"
                                    >
                                        <x-wd-icon name="rotate" size="xs" />
                                        <span x-text="label">Resend code</span>
                                    </button>
                                </form>

                                <p class="mt-2 text-[11px] font-semibold text-ink-400">You can request a new code 30 seconds after the last one.</p>
                            </div>
                        </div>

                        <aside class="border-t border-ink-100 bg-ink-50 p-6 sm:border-l sm:border-t-0 sm:p-8" aria-label="Request summary">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-ink-400">Request summary</p>

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
                                        @if ($customerRequest->variant?->name)
                                            <span class="block text-xs font-semibold text-ink-500">{{ $customerRequest->variant->name }}</span>
                                        @endif
                                    </dd>
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

                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-wide text-ink-400">Contact</dt>
                                    <dd class="mt-0.5 text-sm font-bold text-ink">{{ $customerRequest->name }}</dd>
                                    <dd class="text-xs font-semibold text-ink-500">{{ $customerRequest->email ?: 'No email provided' }}</dd>
                                </div>
                            </dl>

                            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                <p class="text-[11px] font-semibold leading-5 text-amber-900">
                                    Wrong details? Start again from the
                                    <a href="{{ $isSell ? route('sell.create') : route('buy.create') }}" class="font-black underline underline-offset-2">
                                        {{ $isSell ? 'sell form' : 'buy form' }}
                                    </a>.
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>

                <p class="mt-5 text-center text-xs leading-5 text-ink-500">
                    Having trouble? Call
                    <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="link">{{ config('marketplace.brand.phone') }}</a>
                    during {{ config('marketplace.brand.hours') }}.
                </p>
            </div>
        </div>
    </div>
@endsection
