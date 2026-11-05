@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-12">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div>
                    <p class="eyebrow">We are humans, not a chatbot</p>
                    <h1 class="mt-2 max-w-2xl text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">{{ $seo->h1 }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600 sm:text-base">
                        Questions about a listing, a valuation or the buying process? Call, WhatsApp or send a message and we will get back to you within one working day.
                    </p>
                </div>

                <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
                    <p class="text-[11px] font-black uppercase tracking-wide text-ink-400">Business hours</p>
                    <p class="mt-2 text-sm font-black text-ink">{{ config('marketplace.brand.hours') }}</p>
                    <p class="mt-1 text-xs font-semibold text-ink-500">Closed on Sundays and public holidays</p>
                </div>
            </div>
        </div>
    </section>

    <div class="container-page py-10 lg:py-14">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}" class="card group p-5 transition hover:-translate-y-0.5 hover:border-ink hover:shadow-lift">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-ink">
                    <x-wd-icon name="phone" size="sm" />
                </span>
                <p class="mt-4 text-[10px] font-black uppercase tracking-wide text-ink-400">Call us</p>
                <p class="mt-1 text-sm font-black text-ink">{{ config('marketplace.brand.phone') }}</p>
                <p class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-ink-500 group-hover:text-ink">
                    Tap to call <x-wd-icon name="arrow-right" size="xs" />
                </p>
            </a>

            <a href="https://wa.me/{{ config('marketplace.brand.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="card group p-5 transition hover:-translate-y-0.5 hover:border-ink hover:shadow-lift">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ink text-brand-500">
                    <x-wd-icon name="whatsapp" size="sm" />
                </span>
                <p class="mt-4 text-[10px] font-black uppercase tracking-wide text-ink-400">WhatsApp</p>
                <p class="mt-1 text-sm font-black text-ink">Message the team</p>
                <p class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-ink-500 group-hover:text-ink">
                    Open WhatsApp <x-wd-icon name="arrow-right" size="xs" />
                </p>
            </a>

            <a href="mailto:{{ config('marketplace.brand.email') }}" class="card group p-5 transition hover:-translate-y-0.5 hover:border-ink hover:shadow-lift">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ink-50 text-ink">
                    <x-wd-icon name="mail" size="sm" />
                </span>
                <p class="mt-4 text-[10px] font-black uppercase tracking-wide text-ink-400">Email</p>
                <p class="mt-1 break-all text-sm font-black text-ink">{{ config('marketplace.brand.email') }}</p>
                <p class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-ink-500 group-hover:text-ink">
                    Send an email <x-wd-icon name="arrow-right" size="xs" />
                </p>
            </a>

            <div class="card p-5">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ink-50 text-ink">
                    <x-wd-icon name="map-pin" size="sm" />
                </span>
                <p class="mt-4 text-[10px] font-black uppercase tracking-wide text-ink-400">Visit</p>
                <p class="mt-1 text-sm font-black leading-5 text-ink">{{ config('marketplace.brand.address') }}</p>
            </div>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] xl:gap-12">
            <div class="card p-5 sm:p-7 lg:p-8">
                <p class="eyebrow">Send a message</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight">Tell us how we can help</h2>
                <p class="mt-2 text-sm leading-6 text-ink-500">
                    Include the car or reference number if your question is about a specific listing. We reply within one working day.
                </p>

                <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="hidden" aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="label">Full name <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                id="contact-name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autocomplete="name"
                                class="input @error('name') border-red-500 @enderror"
                                @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror
                            >
                            @error('name')
                                <p id="contact-name-error" class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-phone" class="label">Mobile number <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input
                                type="tel"
                                id="contact-phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                required
                                inputmode="tel"
                                autocomplete="tel"
                                placeholder="10-digit mobile number"
                                class="input @error('phone') border-red-500 @enderror"
                                @error('phone') aria-invalid="true" aria-describedby="contact-phone-error" @enderror
                            >
                            @error('phone')
                                <p id="contact-phone-error" class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-email" class="label">Email <span class="font-medium normal-case tracking-normal text-ink-400">(optional)</span></label>
                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                class="input @error('email') border-red-500 @enderror"
                            >
                            @error('email')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-subject" class="label">Subject <span class="text-red-600" aria-hidden="true">*</span></label>
                            <select id="contact-subject" name="subject" required class="input @error('subject') border-red-500 @enderror">
                                <option value="">Choose a topic</option>
                                @foreach ([
                                    'Buying a car' => 'Buying a car',
                                    'Selling my car' => 'Selling my car',
                                    'Existing enquiry' => 'Existing enquiry',
                                    'Listing correction' => 'Listing correction',
                                    'Partnership' => 'Partnership',
                                    'Something else' => 'Something else',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('subject')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="contact-message" class="label">Message <span class="text-red-600" aria-hidden="true">*</span></label>
                        <textarea
                            id="contact-message"
                            name="message"
                            rows="5"
                            required
                            minlength="10"
                            maxlength="3000"
                            class="input resize-y @error('message') border-red-500 @enderror"
                            @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p id="contact-message-error" class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-captcha />

                    <div class="flex flex-col gap-3 border-t border-ink-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="max-w-sm text-xs leading-5 text-ink-500">
                            By sending this message you agree to our
                            <a href="{{ route('pages.privacy') }}" class="link">privacy policy</a>.
                        </p>
                        <button type="submit" class="btn-primary">
                            Send message
                            <x-wd-icon name="arrow-right" size="sm" />
                        </button>
                    </div>
                </form>
            </div>

            <aside class="space-y-4" aria-label="Contact information">
                <div class="rounded-2xl bg-ink p-6 text-white">
                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-brand-500">Our office</p>
                    <p class="mt-3 text-sm font-bold leading-6 text-white">{{ config('marketplace.brand.address') }}</p>
                    <p class="mt-3 text-xs leading-5 text-ink-300">
                        Visits are by appointment so we can make sure the right person and the car are available.
                    </p>

                    <div class="mt-5 border-t border-white/10 pt-4">
                        <p class="text-[11px] font-black uppercase tracking-wide text-ink-400">Hours</p>
                        <p class="mt-1 text-sm font-bold text-white">{{ config('marketplace.brand.hours') }}</p>
                    </div>
                </div>

                <div class="card p-5">
                    <div class="flex items-center gap-2">
                        <x-wd-icon name="shield-check" size="sm" class="text-emerald-600" />
                        <p class="text-sm font-black text-ink">Faster answers</p>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-ink-500">
                        For questions about a specific car, use the enquiry form on the listing. It includes the car reference automatically.
                    </p>
                    <a href="{{ route('vehicles.index') }}" class="btn-outline btn-sm mt-4 w-full">
                        Browse cars
                        <x-wd-icon name="arrow-right" size="xs" />
                    </a>
                </div>

                <div class="card p-5">
                    <p class="text-sm font-black text-ink">Before you write</p>
                    <ul class="mt-3 space-y-2 text-xs leading-5 text-ink-500">
                        <li class="flex gap-2"><x-wd-icon name="check" size="xs" class="mt-0.5 text-emerald-600" /> Include the listing name or reference.</li>
                        <li class="flex gap-2"><x-wd-icon name="check" size="xs" class="mt-0.5 text-emerald-600" /> Tell us the best time to call.</li>
                        <li class="flex gap-2"><x-wd-icon name="check" size="xs" class="mt-0.5 text-emerald-600" /> Never send payment details by email or WhatsApp.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
@endsection
