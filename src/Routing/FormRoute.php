<?php

namespace Larasell\FormDrafts\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Larasell\FormDrafts\Http\Controllers\FormDraftController;

/**
 * Fluent builder for a form's draft routes, mirroring how Laravel's
 * own route registration works: the routes are registered eagerly,
 * and the builder keeps references to the registered Route objects so
 * later calls mutate them in place.
 *
 * ```php
 * Route::middleware('auth')->prefix('dashboard')->group(function (): void {
 *     Route::form(CheckoutForm::class)
 *         ->middleware(EnsureFeaturesAreActive::using('checkout'));
 *
 *     Route::form(ContactForm::class); // fully defaults
 * });
 * ```
 *
 * For each form it registers, inheriting the surrounding group's
 * prefix and middleware:
 *
 *     PATCH   {prefix}/forms/{draftKey}/draft  → FormDraftController@update
 *     DELETE  {prefix}/forms/{draftKey}/draft  → FormDraftController@destroy
 *
 * The form class is embedded in the route's defaults, never taken
 * from request input. The form's model defaults to the authenticated
 * user.
 */
class FormRoute
{
    /**
     * @param  class-string<Form>  $formClass
     */
    public function __construct(
        protected readonly Router $router,
        protected readonly string $formClass,
    ) {
        $this->register();
    }

    protected Route $patch;

    protected Route $delete;

    /**
     * Additional middleware for the form's draft routes.
     *
     * @param  string|list<string>|array<string>  $middleware
     */
    public function middleware(array|string $middleware): static
    {
        $this->patch->middleware($middleware);
        $this->delete->middleware($middleware);

        return $this;
    }

    /**
     * The route name prefix. Defaults to `forms.{draftKey}`, so the
     * routes are named `{prefix}.draft` and `{prefix}.draft.destroy`.
     */
    public function name(string $name): static
    {
        $this->patch->name("{$name}.draft");
        $this->delete->name("{$name}.draft.destroy");

        return $this;
    }

    /**
     * Register the draft routes eagerly and keep references for later
     * fluent mutation.
     */
    protected function register(): void
    {
        $key = $this->formClass::draftKey();

        $this->patch = $this->router->patch("forms/{$key}/draft", [FormDraftController::class, 'update'])
            ->name("forms.{$key}.draft")
            ->defaults('form', $this->formClass);

        $this->delete = $this->router->delete("forms/{$key}/draft", [FormDraftController::class, 'destroy'])
            ->name("forms.{$key}.draft.destroy")
            ->defaults('form', $this->formClass);
    }
}
