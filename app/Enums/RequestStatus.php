<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'Pending OTP',
            self::NEW => 'New',
            self::CONTACTED => 'Contacted',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'gray',
            self::NEW => 'warning',
            self::CONTACTED => 'info',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function isAdminVisible(): bool
    {
        return $this !== self::PENDING_VERIFICATION;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->reject(fn (self $case) => $case === self::PENDING_VERIFICATION)
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
