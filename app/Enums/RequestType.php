<?php

namespace App\Enums;

enum RequestType: string
{
    case SELL = 'sell';
    case BUY = 'buy';

    public function label(): string
    {
        return match ($this) {
            self::SELL => 'Sell my vehicle',
            self::BUY => 'Buy a vehicle',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::SELL => 'Sell',
            self::BUY => 'Buy',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
