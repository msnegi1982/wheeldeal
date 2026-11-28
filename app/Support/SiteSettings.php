<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SiteSettings
{
    public const CACHE_KEY = 'site_settings:values';

    public const KEY_NAME = 'site_name';

    public const KEY_PHONE = 'phone';

    public const KEY_WHATSAPP = 'whatsapp';

    public const KEY_EMAIL = 'email';

    public const KEY_ADDRESS = 'address';

    public const KEY_LOGO = 'logo';

    /** @var array<string, string>|null */
    private static ?array $values = null;

    private static bool $applied = false;

    /**
     * @return array<string, string>
     */
    public static function values(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return self::$values = [];
            }

            /** @var array<string, string> $values */
            $values = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
                ->pluck('value', 'key')
                ->all());

            return self::$values = $values;
        } catch (Throwable) {
            return self::$values = [];
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::values()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function apply(): void
    {
        if (self::$applied) {
            return;
        }

        $values = self::values();

        if ($values === []) {
            self::$applied = true;

            return;
        }

        $originalName = (string) config('app.name');
        $name = self::get(self::KEY_NAME);

        if ($name !== null) {
            config([
                'app.name' => $name,
                'seo.site_name' => $name,
                'marketplace.brand.name' => $name,
            ]);

            $defaultTitle = (string) config('seo.default_title');

            if ($originalName !== '' && str_starts_with($defaultTitle, $originalName)) {
                config([
                    'seo.default_title' => $name.substr($defaultTitle, strlen($originalName)),
                ]);
            }
        }

        $phone = self::get(self::KEY_PHONE);

        if ($phone !== null) {
            config(['marketplace.brand.phone' => $phone]);
        }

        $whatsapp = self::get(self::KEY_WHATSAPP);

        if ($whatsapp !== null) {
            config(['marketplace.brand.whatsapp' => $whatsapp]);
        }

        $email = self::get(self::KEY_EMAIL);

        if ($email !== null) {
            config(['marketplace.brand.email' => $email]);
        }

        $address = self::get(self::KEY_ADDRESS);

        if ($address !== null) {
            config(['marketplace.brand.address' => $address]);
        }

        config([
            'marketplace.brand.logo' => self::get(self::KEY_LOGO),
        ]);

        self::$applied = true;
    }

    public static function reload(): void
    {
        self::flush();
        self::apply();
    }

    public static function flush(): void
    {
        self::$values = null;
        self::$applied = false;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Ignore cache failures; settings will still be read from the database.
        }
    }
}
