<?php

namespace Larasell\FormDrafts;

use Illuminate\Support\Facades\Cache;
use Larasell\FormDrafts\Contracts\DraftStore;

/**
 * Persists form drafts in the cache with a limited lifetime, so
 * abandoned drafts expire on their own.
 */
class CacheDraftStore implements DraftStore
{
    public function __construct(
        protected readonly ?int $ttl = null,
        protected readonly string $prefix = 'form-draft',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(Form $form): array
    {
        return Cache::get($this->key($form), []);
    }

    public function write(Form $form, array $draft): void
    {
        $key = $this->key($form);

        $existing = Cache::get($key, []);

        Cache::put($key, array_merge($existing, $draft), $this->ttl);
    }

    public function clear(Form $form): void
    {
        Cache::forget($this->key($form));
    }

    /**
     * A draft key scoped to the form and its owning model.
     */
    protected function key(Form $form): string
    {
        $model = $form->model();

        return sprintf(
            '%s:%s:%s',
            $this->prefix,
            $form::class,
            $model !== null ? $model->getKey() : 'guest',
        );
    }
}
