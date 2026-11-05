@extends('layouts.app')

@section('content')
    <section class="page-hero">
        <div class="container-page py-8 lg:py-12">
            <x-breadcrumbs :items="$seo->breadcrumbs" />

            <div class="mt-4 max-w-3xl">
                <p class="eyebrow">Your information, handled carefully</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">{{ $seo->h1 }}</h1>
                <p class="mt-3 text-sm leading-7 text-ink-600 sm:text-base">
                    This policy explains what information {{ config('seo.site_name') }} collects, why we collect it and the choices you have.
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
                            'information-we-collect' => 'Information we collect',
                            'how-we-use-it' => 'How we use it',
                            'sharing' => 'When we share it',
                            'cookies' => 'Cookies and analytics',
                            'retention' => 'How long we keep it',
                            'security' => 'Security',
                            'your-rights' => 'Your rights',
                            'contact' => 'Contact us',
                        ] as $anchor => $label)
                            <li><a href="#{{ $anchor }}" class="transition hover:text-ink hover:underline hover:decoration-brand-500 hover:decoration-2 hover:underline-offset-4">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <article class="prose-wheeldeal max-w-3xl">
                <p>
                    {{ config('seo.site_name') }} is a used-vehicle marketplace. We collect only the information needed to operate the catalogue, respond to enquiries and keep the service safe. This policy applies to the public website and the forms on it.
                </p>

                <h2 id="information-we-collect">Information we collect</h2>
                <p>We may collect the following categories of information:</p>
                <ul>
                    <li><strong>Contact details</strong> you submit through a sell request, buy request, vehicle enquiry or contact form: name, mobile number, optional email address and city.</li>
                    <li><strong>Request details</strong> such as the vehicle brand, model, year, kilometres, expected price, condition, colour and any notes or photos you choose to share.</li>
                    <li><strong>Verification data</strong> including whether a one-time password was sent, delivered and verified, the delivery channel and the time of verification. We store a hash of the code, not the code itself.</li>
                    <li><strong>Technical data</strong> such as IP address, browser user agent, pages viewed and basic request logs, used for security, rate limiting and troubleshooting.</li>
                    <li><strong>Communications</strong> you send to our team by phone, WhatsApp, email or the contact form.</li>
                </ul>
                <p>We do not ask for payment card details through this website, and we do not create customer accounts for public browsing.</p>

                <h2 id="how-we-use-it">How we use it</h2>
                <p>We use your information to:</p>
                <ul>
                    <li>Respond to your enquiry or request and contact you about the vehicle or service you asked about.</li>
                    <li>Verify that a request comes from a real person and reduce spam, fraud and automated abuse.</li>
                    <li>Prepare, review and publish vehicle listings where you have asked us to sell a vehicle.</li>
                    <li>Operate, secure and improve the website, including diagnosing errors and understanding which pages are useful.</li>
                    <li>Comply with applicable legal obligations and enforce our terms.</li>
                </ul>
                <p>
                    We rely on your consent where we contact you about a request, and on our legitimate interests in operating a secure marketplace and responding to enquiries. You can withdraw consent at any time by contacting us, though we may still need to keep records for legal or fraud-prevention purposes.
                </p>

                <h2 id="sharing">When we share it</h2>
                <p>We do not sell your personal information. We may share it with:</p>
                <ul>
                    <li><strong>Service providers</strong> that help us run the website, send SMS or WhatsApp messages, host data, prevent abuse or provide analytics, under appropriate confidentiality and data-processing terms.</li>
                    <li><strong>Our team and authorised representatives</strong> who need the information to respond to your request.</li>
                    <li><strong>Authorities or counterparties</strong> where required by law, to investigate fraud or abuse, or to protect the rights and safety of our users and the public.</li>
                </ul>
                <p>
                    If you ask about a specific vehicle, we may share your enquiry details with the person responsible for that listing so they can respond. We do not publish your phone number on a listing.
                </p>

                <h2 id="cookies">Cookies and analytics</h2>
                <p>
                    The website uses essential cookies and similar storage to keep forms working, remember a session and protect against cross-site request forgery. We may use privacy-respecting analytics to understand aggregate traffic and page performance. We do not use cookies to build advertising profiles across unrelated websites.
                </p>
                <p>
                    You can block or delete cookies in your browser. Blocking essential cookies may prevent forms and verification from working.
                </p>

                <h2 id="retention">How long we keep it</h2>
                <p>
                    We keep request and enquiry records for as long as needed to handle the request and maintain a reasonable business record, then delete or anonymise them. Verification records are kept for a shorter period for security and audit purposes. Server logs are rotated regularly. If a legal obligation requires a longer retention period, we will keep the relevant records for that period.
                </p>

                <h2 id="security">Security</h2>
                <p>
                    We use HTTPS, access controls, rate limiting, hashed one-time passwords and upload validation to protect the service. No internet transmission is completely secure, so please avoid sending sensitive documents or payment details through the public forms. If you believe your information has been compromised, contact us immediately.
                </p>

                <h2 id="your-rights">Your rights</h2>
                <p>Subject to applicable law, you may ask us to:</p>
                <ul>
                    <li>Confirm what personal information we hold about you and provide a copy.</li>
                    <li>Correct inaccurate information.</li>
                    <li>Delete information we no longer need to keep.</li>
                    <li>Withdraw consent to marketing or follow-up contact.</li>
                    <li>Raise a concern about how we have handled your information.</li>
                </ul>
                <p>
                    To make a request, contact us using the details below. We may need to verify your identity before acting. We will respond within a reasonable period.
                </p>

                <h2 id="contact">Contact us</h2>
                <p>
                    Questions about this policy or your information can be sent to
                    <a href="mailto:{{ config('marketplace.brand.email') }}">{{ config('marketplace.brand.email') }}</a>
                    or by post to {{ config('marketplace.brand.address') }}. You can also call
                    <a href="tel:{{ preg_replace('/\s+/', '', config('marketplace.brand.phone')) }}">{{ config('marketplace.brand.phone') }}</a>
                    during {{ config('marketplace.brand.hours') }}.
                </p>
                <p>
                    We may update this policy when our practices change. The date at the top of the page shows when it was last revised.
                </p>

                <div class="mt-10 rounded-2xl border border-ink-100 bg-sand p-5 not-prose">
                    <p class="text-sm font-black text-ink">Need to correct or delete something?</p>
                    <p class="mt-1 text-xs leading-5 text-ink-500">Send us the mobile number or email you used and tell us what you would like us to do.</p>
                    <a href="{{ route('contact') }}" class="btn-primary btn-sm mt-4">
                        Contact us
                        <x-wd-icon name="arrow-right" size="xs" />
                    </a>
                </div>
            </article>
        </div>
    </div>
@endsection
