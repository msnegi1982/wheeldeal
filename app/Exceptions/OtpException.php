<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

abstract class OtpException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message, protected array $context = [])
    {
        parent::__construct($message);
    }

    abstract public function statusCode(): int;

    public function errorCode(): string
    {
        return 'otp_error';
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [];
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $payload = array_merge([
            'message' => $this->getMessage(),
            'error' => $this->errorCode(),
        ], $this->context());

        if ($request->expectsJson()) {
            return response()->json($payload, $this->statusCode())->withHeaders($this->headers());
        }

        return back()
            ->withInput($request->except(['code', 'captcha_token', 'cf-turnstile-response', 'g-recaptcha-response']))
            ->withErrors(['code' => $this->getMessage()]);
    }
}
