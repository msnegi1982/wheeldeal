<?php

namespace App\Enums;

enum Transmission: string
{
    case MANUAL = 'manual';
    case AUTOMATIC = 'automatic';
    case AMT = 'amt';
    case CVT = 'cvt';
    case DCT = 'dct';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::AUTOMATIC => 'Automatic',
            self::AMT => 'AMT',
            self::CVT => 'CVT',
            self::DCT => 'DCT',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
