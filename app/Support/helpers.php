<?php

use Illuminate\Support\Str;

if (! function_exists('format_inr')) {
    /**
     * Format an amount in Indian rupees with Indian digit grouping, or compactly
     * (e.g. ₹12.5 L / ₹1.2 Cr) for cards and filters.
     */
    function format_inr(int|float|null $amount, bool $compact = false): string
    {
        $amount = (float) ($amount ?? 0);

        if ($compact) {
            if ($amount >= 10000000) {
                return '₹'.rtrim(rtrim(number_format($amount / 10000000, 2), '0'), '.').' Cr';
            }

            if ($amount >= 100000) {
                return '₹'.rtrim(rtrim(number_format($amount / 100000, 2), '0'), '.').' L';
            }

            if ($amount >= 1000) {
                return '₹'.rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.').'k';
            }
        }

        $amount = (int) round($amount);
        $lastThree = substr((string) $amount, -3);
        $rest = substr((string) $amount, 0, -3);

        if ($rest !== '') {
            $lastThree = ','.$lastThree;
        }

        return '₹'.preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).$lastThree;
    }
}

if (! function_exists('format_km')) {
    function format_km(int|float|null $km): string
    {
        return number_format((int) ($km ?? 0)).' km';
    }
}

if (! function_exists('initials')) {
    function initials(string $value, int $length = 2): string
    {
        return Str::of($value)
            ->explode(' ')
            ->filter()
            ->take($length)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
