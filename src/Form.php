<?php

namespace Larasell\Cushion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Larasell\Cushion\Contracts\DraftStore;
use Larasell\Cushion\Routing\FormRoute;

/**
 * Defines the shape of a form: its fields, their validation rules,
 * contextual defaults and the persisted draft.
 *
 * ```php
 * final class CheckoutForm extends Form
 * {
 *     public function fields(): array
 *     {
 *         return [
 *             'company' => 'string|max:255',
 *             'first_name' => 'string|max:255|required',
 *         ];
 *     }
 *
 *     public function defaults(User $user): array
 *     {
 *         return [
 *             'first_name' => str($user->name)->beforeLast(' '),
 *         ];
 *     }
 * }
 * ```
 */
abstract class Form
{
    /**
     * The key identifying this form in draft routes, derived from the
     * class basename by convention (`CheckoutForm` => `checkout`).
     * Override for custom keys.
     */
    public static function draftKey(): string
    {
        return str(class_basename(static::class))
            ->beforeLast('Form')
            ->kebab()
            ->toString();
    }

    /**
     * Register a form's draft routes, equivalent to
     * `Route::form(...)`. Returns the fluent builder for further
     * customization.
     *
     * ```php
     * Form::add(CheckoutForm::class)
     *     ->middleware(EnsureFeaturesAreActive::using('checkout'));
     * ```
     *
     * @param  class-string<static>  $formClass
     */
    public static function add(string $formClass): FormRoute
    {
        return new FormRoute(app(Router::class), $formClass);
    }

    /**
     * @param  string|null  $draftDriver  Draft store driver name from
     *                                    config/cushion.php; null uses the configured default.
     */
    public function __construct(
        protected readonly ?Model $model = null,
        protected readonly ?string $draftDriver = null,
    ) {
        $this->ownerKey = $this->resolveOwnerKey();
    }

    /**
     * The key scoping this form's draft to its owner, bound eagerly
     * at construction: the owning model's key, the authenticated
     * user's id, or the guest's session id. The shared 'guest'
     * fallback only applies outside a request (e.g. console).
     */
    protected string|int $ownerKey;

    /**
     * A form bound to the current owner: the authenticated user,
     * or the guest's session. Because the owner is resolved
     * eagerly, the instance keeps its binding even if the
     * request state changes afterwards (e.g. a login).
     */
    public static function get(): static
    {
        return new static;
    }

    /**
     * The key this form's draft is scoped to.
     */
    public function draftOwnerKey(): string|int
    {
        return $this->ownerKey;
    }

    /**
     * Resolve the draft owner key at construction time. Override to
     * scope drafts differently; the model wins over ambient state.
     */
    protected function resolveOwnerKey(): string|int
    {
        if ($this->model !== null) {
            return $this->model->getKey();
        }

        if (auth()->check()) {
            return auth()->id();
        }

        return app('session.store')->getId();
    }

    /**
     * The draft store for this form, resolved from the configured
     * drivers.
     */
    protected function draftStore(): DraftStore
    {
        return app(DraftStoreManager::class)->resolve($this->draftDriver);
    }

    /**
     * The model the form belongs to, if any.
     */
    public function model(): ?Model
    {
        return $this->model;
    }

    /**
     * The form's fields with their validation rules. Rules use Laravel's
     * pipe syntax; `required` marks a field as required on submit, while
     * draft validation always treats every field as optional.
     *
     * @return array<string, string|object|array<int, string|object>>
     */
    abstract public function fields(): array;

    // -- Validation ------------------------------------------------------------

    /**
     * @return array<string, array<int, string|object>>
     */
    private function parseRules(): array
    {
        $rules = [];

        foreach ($this->fields() as $name => $rulesDefinition) {
            $rules[$name] = is_string($rulesDefinition)
                ? explode('|', $rulesDefinition)
                : Arr::wrap($rulesDefinition);
        }

        return $rules;
    }

    /**
     * Rules for a full form submission.
     *
     * @return array<string, array<int, string|object>>
     */
    public function submitRules(): array
    {
        return array_map(
            static fn (array $rules) => in_array('required', $rules, true)
                ? $rules
                : ['nullable', ...$rules],
            $this->parseRules(),
        );
    }

    /**
     * Rules for a partial draft save. Every field is optional.
     *
     * @return array<string, array<int, string|object>>
     */
    public function draftRules(): array
    {
        return array_map(
            static fn (array $rules) => ['nullable', ...array_values(
                array_filter($rules, static fn (string|object $rule) => $rule !== 'required'),
            )],
            $this->parseRules(),
        );
    }

    // -- Draft -----------------------------------------------------------------

    /**
     * Persist a validated (partial) draft.
     *
     * @param  array<string, mixed>  $draft
     */
    public function saveDraft(array $draft): void
    {
        $this->draftStore()->write($this, Arr::only($draft, array_keys($this->fields())));
    }

    /**
     * Remove the persisted draft.
     */
    public function clearDraft(): void
    {
        $this->draftStore()->clear($this);
    }

    /**
     * The stored draft values.
     *
     * @return array<string, mixed>
     */
    public function draft(): array
    {
        return $this->draftStore()->read($this);
    }

    /**
     * Move this form's draft to another owner, e.g. promoting a
     * guest draft to the authenticated user after login. Returns
     * the target form for chaining.
     */
    public function transfer(Model $to): static
    {
        $target = new static($to, $this->draftDriver);

        $draft = $this->draft();

        if ($draft !== []) {
            $target->saveDraft($draft);
            $this->clearDraft();
        }

        return $target;
    }

    /**
     * The form's values for rendering: stored draft values win over
     * contextual defaults.
     *
     * The optional `defaults()` method receives the same arguments
     * and returns the default values for the fields:
     *
     * ```php
     *     public function defaults(User $user): array
     *     {
     *         return [
     *             'first_name' => str($user->name)->beforeLast(' '),
     *         ];
     *     }
     * ```
     *
     * @param  mixed  ...$context  Arguments forwarded to defaults().
     * @return array<string, mixed>
     */
    public function hydrate(mixed ...$context): array
    {
        $draft = $this->draft();
        $defaults = method_exists($this, 'defaults') ? $this->defaults(...$context) : [];

        $values = [];

        foreach (array_keys($this->fields()) as $name) {
            $values[$name] = array_key_exists($name, $draft) && $draft[$name] !== null
                ? $draft[$name]
                : ($defaults[$name] ?? null);
        }

        return $values;
    }
}
