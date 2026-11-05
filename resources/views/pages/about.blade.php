@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-12">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                <div>
                    <p class="eyebrow">About {{ config('seo.site_name') }}</p>
                    <h1 class="mt-2 max-w-2xl text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">{{ $seo->h1 }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600 sm:text-base">
                        Buying a used car should not feel like detective work. We built {{ config('seo.site_name') }} to make the information clear, the process human and the next step obvious.
                    </p>
                </div>

                <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                    <p class="text-[11px] font-black uppercase tracking-wide text-ink-400">{{ config('marketplace.brand.tagline') }}</p>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-2xl font-black text-ink">360°</p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-ink-400">Views</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-ink">OTP</p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-ink-400">Verified</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-ink">1:1</p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-ink-400">Human help</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container-page py-12 lg:py-16">
        <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">
            <div>
                <p class="eyebrow">Why we exist</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Used car shopping, minus the noise</h2>
            </div>

            <div class="prose-wheeldeal">
                <p>
                    The used car market is full of great cars and terrible shortcuts. Listings leave out the details that change a decision: who owned the car, how it was used, what the kilometres really mean and whether the price is fair. Buyers end up chasing phone numbers; sellers end up fielding time-wasters.
                </p>
                <p>
                    {{ config('seo.site_name') }} takes a narrower approach. We publish the specification, ownership, condition and location details we can stand behind, show the car properly and connect serious buyers with a real person. Sellers get a structured valuation conversation instead of a public free-for-all. Buyers get a shortlist worth their time.
                </p>
                <p>
                    We are not a bank, an auction house or an anonymous classifieds board. We are a small team that would rather answer one good question than send ten automated follow-ups.
                </p>
            </div>
        </div>

        <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['search', 'Clear listings', 'Specs, ownership, kilometres and city on every card — before you click.'],
                ['shield-check', 'Verified contact', 'Sell and buy requests are confirmed with a 6-digit mobile code.'],
                ['rotate', 'See more', 'Photo galleries and 360° views help you shortlist from home.'],
                ['phone', 'A real conversation', 'Call, WhatsApp or enquire. A person replies during business hours.'],
            ] as [$icon, $title, $copy])
                <div class="card p-5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-ink">
                        <x-wd-icon :name="$icon" size="sm" />
                    </span>
                    <p class="mt-4 text-sm font-black text-ink">{{ $title }}</p>
                    <p class="mt-1.5 text-xs leading-5 text-ink-500">{{ $copy }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-14 grid gap-8 rounded-3xl bg-ink p-6 text-white sm:p-10 lg:grid-cols-[1fr_1fr] lg:gap-16">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-500">How we work</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Straight answers, sensible next steps.</h2>
                <p class="mt-4 max-w-md text-sm leading-7 text-ink-200">
                    Whether you are buying or selling, the process is designed to remove pressure. You always know what happens next and you can stop at any point.
                </p>
                <a href="{{ route('contact') }}" class="btn-primary mt-6">
                    Talk to the team
                    <x-wd-icon name="arrow-right" size="sm" />
                </a>
            </div>

            <div class="space-y-3">
                @foreach ([
                    ['01', 'Share the basics', 'A short form, your city and a verified mobile number.'],
                    ['02', 'We review and call', 'A real person checks the details and discusses the practical next step.'],
                    ['03', 'Inspect in person', 'Documents, condition and a proper test drive before any commitment.'],
                    ['04', 'Decide with clarity', 'Agree the price and transfer with a written process, not a handshake guess.'],
                ] as [$number, $title, $copy])
                    <div class="flex gap-4 rounded-xl border border-white/10 bg-white/5 p-4">
                        <span class="font-mono text-sm font-black text-brand-500">{{ $number }}</span>
                        <div>
                            <p class="text-sm font-black text-white">{{ $title }}</p>
                            <p class="mt-1 text-xs leading-5 text-ink-300">{{ $copy }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-14 grid gap-8 lg:grid-cols-2 lg:gap-16">
            <div>
                <p class="eyebrow">What we value</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Trust is built in the details</h2>
                <ul class="mt-5 space-y-4">
                    @foreach ([
                        ['Transparency over polish', 'We would rather tell you what we do not know than fill the gap with marketing language.'],
                        ['Useful beats clever', 'Fast pages, readable listings and forms that respect your time.'],
                        ['Consent matters', 'Your number is used to respond to your request. It is never published or sold.'],
                        ['The car has to be right', 'A good listing is not a substitute for an inspection and a test drive.'],
                    ] as [$title, $copy])
                        <li class="flex gap-3">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-500 text-ink">
                                <x-wd-icon name="check" size="xs" />
                            </span>
                            <div>
                                <p class="text-sm font-black text-ink">{{ $title }}</p>
                                <p class="mt-1 text-xs leading-5 text-ink-500">{{ $copy }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-2xl border border-ink-100 bg-sand p-6 sm:p-8">
                <p class="eyebrow">The small print, in plain words</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight">What we are not</h2>
                <div class="mt-4 space-y-4 text-sm leading-7 text-ink-600">
                    <p>
                        We do not own every car listed. Listings are prepared with the seller and reviewed before publishing, but the final inspection, paperwork and transaction are between you and the seller.
                    </p>
                    <p>
                        We do not guarantee finance, insurance or a resale value. If a service is introduced by a partner, the terms are explained separately before you commit.
                    </p>
                    <p>
                        We do not publish your phone number. Sell and buy requests are private and verified before our team follows up.
                    </p>
                </div>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('pages.terms') }}" class="chip">Terms of use</a>
                    <a href="{{ route('pages.privacy') }}" class="chip">Privacy policy</a>
                    <a href="{{ route('contact') }}" class="chip">Contact us</a>
                </div>
            </div>
        </div>
    </div>
@endsection
