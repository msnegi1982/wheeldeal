<?php

namespace App\Observers;

use App\Services\Support\CacheService;
use Illuminate\Database\Eloquent\Model;

class CatalogCacheObserver
{
    public function __construct(private CacheService $cache) {}

    public function saved(Model $model): void
    {
        $this->cache->flushCatalog();
    }

    public function deleted(Model $model): void
    {
        $this->cache->flushCatalog();
    }
}
