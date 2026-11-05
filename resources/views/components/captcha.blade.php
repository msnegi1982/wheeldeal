@php
    $turnstileSiteKey = config('services.turnstile.site_key');
    $recaptchaSiteKey = config('services.recaptcha.site_key');
@endphp

@if ($turnstileSiteKey)
    <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light" data-language="en"></div>
    @push('scripts')
        @once
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endonce
    @endpush
@elseif ($recaptchaSiteKey)
    <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
    @push('scripts')
        @once
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endonce
    @endpush
@endif

@error('captcha_token')
    <p class="mt-1 text-xs font-bold text-red-700" role="alert">{{ $message }}</p>
@enderror
