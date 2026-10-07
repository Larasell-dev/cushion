<?php

namespace Larasell\Cushion\Contracts;

use Larasell\Cushion\Form;

/**
 * Persists a form's draft. Implementations decide where drafts live —
 * the cache, the database, or elsewhere.
 */
interface DraftStore
{
    /**
     * The stored draft values for the given form.
     *
     * @return array<string, mixed>
     */
    public function read(Form $form): array;

    /**
     * Persist the given (partial) draft for the form.
     *
     * @param  array<string, mixed>  $draft
     */
    public function write(Form $form, array $draft): void;

    /**
     * Remove the stored draft for the form.
     */
    public function clear(Form $form): void;
}
