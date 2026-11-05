<?php

namespace App\Support;

final class PhoneNumber
{
    /**
     * Normalise an Indian mobile number to country-code + digits (e.g. 919876543210).
     * This is the format stored in the database and sent to providers.
     */
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && preg_match('/^[6-9]/', $digits) === 1) {
            $digits = '91'.$digits;
        }

        return $digits;
    }

    public static function e164(?string $value): string
    {
        return '+'.self::normalize($value);
    }

    public static function isValidIndianMobile(?string $value): bool
    {
        return preg_match('/^91[6-9]\d{9}$/', self::normalize($value)) === 1;
    }

    public static function masked(?string $value): string
    {
        $normalized = self::normalize($value);

        if (strlen($normalized) < 4) {
            return '••••';
        }

        return '+'.substr($normalized, 0, 2).' ••••• '.substr($normalized, -4);
    }
}
