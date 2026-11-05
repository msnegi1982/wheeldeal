@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-12">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 max-w-3xl">
                <p class="eyebrow">The rules of the road</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">{{ $seo->h1 }}</h1>
                <p class="mt-3 text-sm leading-7 text-ink-600 sm:text-base">
                    These terms apply when you browse {{ config('seo.site_name') }}, submit a vehicle request, send an enquiry or contact our team.
                </p>
                <p class="mt-3 text-xs font-bold uppercase tracking-wide text-ink-400">Last updated 4 November 2026</p>
            </div>
        </div>
    </section>

    <div class="container-page py-10 lg:py-14">
        <div class="grid gap-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-16">
            <aside class="hidden lg:block">
                <nav class="sticky top-24 rounded-2xl border border-ink-100 bg-white p-4" aria-label="On this page">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-ink-400">On this page</p>
                    <ul class="mt-3 space-y-2 text-xs font-bold text-ink-600">
                        @foreach ([
                            'acceptance' => 'Acceptance',
                            'service' => 'The service',
                            'listings' => 'Listings and pricing',
                            'requests' => 'Requests and verification',
                            'acceptable-use' => 'Acceptable use',
                            'intellectual-property' => 'Intellectual property',
                            'third-parties' => 'Third-party links',
                            'disclaimers' => 'Disclaimers',
                            'liability' => 'Liability',
                            'changes' => 'Changes and law',
                            'contact' => 'Contact',
                        ] as $anchor => $label)
                            <li><a href="#{{ $anchor }}" class="transition hover:text-ink hover:underline hover:decoration-brand-500 hover:decoration-2 hover:underline-offset-4">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <article class="prose-wheeldeal max-w-3xl">
                <p>
                    Please read these terms carefully. By using this website or submitting a form, you agree to them. If you do not agree, please do not use the service.
                </p>

                <h2 id="acceptance">1. Acceptance</h2>
                <p>
                    These terms form an agreement between you and {{ config('seo.site_name') }} (referred to as "we", "us" or "our"). You must be at least 18 years old and legally able to enter into a contract to submit a sell or buy request. If you use the site on behalf of a business, you confirm that you are authorised to accept these terms for that business.
                </p>

                <h2 id="service">2. The service</h2>
                <p>
                    {{ config('seo.site_name') }} provides a catalogue of used four-wheelers and a way to enquire about them or ask us to help you sell a vehicle. We may change, suspend or discontinue any part of the service at any time.
                </p>
                <p>
                    We are not a party to every transaction between a buyer and a seller. Unless we expressly agree in writing, we do not own the vehicle, act as your agent, provide finance, insure the vehicle or guarantee its condition, ownership or paperwork.
                </p>

                <h2 id="listings">3. Listings and pricing</h2>
                <p>
                    We aim to keep listings accurate, but specifications, photographs, availability and prices can change. A listing is an invitation to enquire, not a binding offer. Prices are shown in Indian rupees and may exclude insurance, transfer, road tax or other charges unless stated otherwise.
                </p>
                <p>
                    You should verify the vehicle in person, inspect the documents and arrange an independent mechanical check before paying. Photographs and 360° views are for illustration and may not show every defect.
                </p>

                <h2 id="requests">4. Requests and verification</h2>
                <p>
                    Sell and buy requests require a valid Indian mobile number and a one-time password. You agree to provide accurate information and not to submit a request on behalf of someone else without their permission. Codes expire after five minutes and are limited to a small number of attempts.
                </p>
                <p>
                    Submitting a request does not create an obligation for us to buy, sell, list or value a vehicle. We may decline or remove a request that is incomplete, misleading, unlawful or inconsistent with these terms.
                </p>

                <h2 id="acceptable-use">5. Acceptable use</h2>
                <p>You must not:</p>
                <ul>
                    <li>Submit false, misleading or fraudulent information, or impersonate another person.</li>
                    <li>Use the service to harass, threaten or discriminate against anyone.</li>
                    <li>Attempt to bypass rate limits, verification, access controls or security measures.</li>
                    <li>Scrape, copy or republish the catalogue or other users' information without permission.</li>
                    <li>Upload malicious code, unlawful content or images you do not have the right to share.</li>
                    <li>Use the service for spam, unsolicited marketing or any unlawful purpose.</li>
                </ul>

                <h2 id="intellectual-property">6. Intellectual property</h2>
                <p>
                    The website design, text, graphics, logos and software are owned by us or licensed to us and are protected by applicable intellectual-property laws. You may view and print pages for personal, non-commercial use. You may not reproduce, modify, distribute or commercially exploit the content without our prior written permission.
                </p>
                <p>
                    When you upload photographs or other content, you confirm that you have the right to share it and grant us a non-exclusive licence to use, resize, display and promote it in connection with the service. You can ask us to remove your content by contacting us.
                </p>

                <h2 id="third-parties">7. Third-party links and services</h2>
                <p>
                    The website may link to third-party sites such as WhatsApp, maps or social media. We do not control those services and are not responsible for their content, terms or privacy practices. Use them at your own discretion.
                </p>

                <h2 id="disclaimers">8. Disclaimers</h2>
                <p>
                    The service is provided on an "as is" and "as available" basis. To the fullest extent permitted by law, we disclaim implied warranties of merchantability, fitness for a particular purpose and non-infringement. We do not warrant that the site will be uninterrupted, error-free or free of harmful components, or that any listing is complete or accurate.
                </p>
                <p>
                    Nothing in these terms excludes rights you have under mandatory consumer-protection law.
                </p>

                <h2 id="liability">9. Liability</h2>
                <p>
                    To the extent permitted by law, we are not liable for indirect, incidental, special or consequential loss, loss of profit, loss of opportunity or loss arising from a transaction between you and another person. Where liability cannot be excluded, our total liability arising out of or in connection with the service is limited to the amount you paid us for the specific service giving rise to the claim, or ₹10,000 if no amount was paid, whichever is higher.
                </p>

                <h2 id="changes">10. Changes and governing law</h2>
                <p>
                    We may update these terms from time to time. The revised version applies when it is published on this page. If any provision is found unenforceable, the remaining provisions continue to apply.
                </p>
                <p>
                    These terms are governed by the laws of India. Subject to any mandatory consumer forum rights, the courts at Bengaluru, Karnataka have exclusive jurisdiction over disputes arising from the service.
                </p>

                <h2 id="contact">11. Contact</h2>
                <p>
                    Questions about these terms? Email
                    <a href="mailto:{{ config('marketplace.brand.email') }}">{{ config('marketplace.brand.email') }}</a>,
                    call <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}">{{ config('marketplace.brand.phone') }}</a>
                    or write to {{ config('marketplace.brand.address') }}.
                </p>

                <div class="mt-10 flex flex-wrap gap-2 not-prose">
                    <a href="{{ route('pages.privacy') }}" class="chip">Privacy policy</a>
                    <a href="{{ route('contact') }}" class="chip">Contact us</a>
                    <a href="{{ route('vehicles.index') }}" class="chip">Browse cars</a>
                </div>
            </article>
        </div>
    </div>
@endsection
