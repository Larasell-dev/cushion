<?php

namespace Larasell\Cushion;

use Illuminate\Support\Manager;
use Larasell\Cushion\Contracts\DraftStore;

/**
 * Resolves draft store drivers from config/cushion.php.
 *
 * ```php
 * $store = app(DraftStoreManager::class)->driver(); // default driver
 * ```
 */
class DraftStoreManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('cushion.driver', 'cache');
    }

    protected function createCacheDriver(): DraftStore
    {
        return new CacheDraftStore(
            ttl: config('cushion.ttl'),
        );
    }

    /**
     * Resolve a driver by name, falling back to the default driver.
     */
    public function resolve(?string $driver): DraftStore
    {
        return $driver !== null && $driver !== ''
            ? $this->driver($driver)
            : $this->driver();
    }

    public function store(?string $driver = null): DraftStore
    {
        return $this->resolve($driver);
    }
}
