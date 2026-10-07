<?php

namespace Larasell\FormDrafts\Routing;

use Illuminate\Routing\Router;

/**
 * Registers the `Route::form()` macro, provided by the package's
 * service provider.
 *
 * `Route::form(CheckoutForm::class)` returns a fluent builder that
 * registers the form's draft endpoints (see FormRoute).
 */
class DraftRouteMacro
{
    public static function register(): void
    {
        Router::macro('form', function (string $formClass): FormRoute {
            /** @var Router $this */
            /** @var class-string<Form> $formClass */
            return new FormRoute($this, $formClass);
        });
    }
}
