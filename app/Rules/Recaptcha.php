<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Verifies Cloudflare Turnstile or Google reCAPTCHA v2/v3 tokens.
 *
 * When no secret key is configured (local/demo), the check is skipped. As
 * soon as a key exists it is enforced, so a production deploy cannot silently
 * run without bot protection.
 */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $turnstileSecret = config('services.turnstile.secret_key');
        $recaptchaSecret = config('services.recaptcha.secret_key');

        $secret = $turnstileSecret ?: $recaptchaSecret;

        if (! $secret) {
            return;
        }

        $token = $value ?: request()->input('cf-turnstile-response') ?: request()->input('g-recaptcha-response');

        if (! is_string($token) || trim($token) === '') {
            $fail('Please complete the verification challenge.');

            return;
        }

        $url = $turnstileSecret
            ? config('services.turnstile.verify_url')
            : config('services.recaptcha.verify_url');

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post($url, [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable $e) {
            report($e);
            $fail('We could not verify the challenge. Please try again.');

            return;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            $fail('Verification failed. Please refresh the page and try again.');
        }
    }
}
