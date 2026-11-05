<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PhoneNumber::isValidIndianMobile(is_string($value) ? $value : null)) {
            $fail('Enter a valid 10-digit Indian mobile number.');
        }
    }
}
