<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OtpNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Verification session not found.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): mixed
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'otp_not_found',
            ], 404);
        }

        return back()->withErrors(['code' => $this->getMessage()]);
    }
}
