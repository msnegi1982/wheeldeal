<?php

namespace App\Services\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const VERSION_KEY = 'catalog:version';

    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        $ttl ??= (int) config('marketplace.cache.ttl', 600);

        return Cache::remember($this->key($key), $ttl, $callback);
    }

    public function forget(string $key): void
    {
        Cache::forget($this->key($key));
    }

    public function flushCatalog(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    public function key(string $key): string
    {
        return config('marketplace.cache.prefix', 'wheeldeal').':v'.$this->version().':'.$key;
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
