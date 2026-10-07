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
 *     public function defaults(): array
 *     {
 *         return [
 *             'first_name' => fn (User $user) => str($user->name)->beforeLast(' '),
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
    ) {}

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

    /**
     * Contextual default values, resolved with the arguments given to
     * Form::hydrate(). Ignored when the stored draft holds a value.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [];
    }

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
     * The form's values for rendering: stored draft values win over
     * contextual defaults.
     *
     * @param  mixed  ...$context  Arguments passed to default callables.
     * @return array<string, mixed>
     */
    public function hydrate(mixed ...$context): array
    {
        $draft = $this->draft();

        $values = [];

        foreach (array_keys($this->fields()) as $name) {
            $values[$name] = array_key_exists($name, $draft) && $draft[$name] !== null
                ? $draft[$name]
                : $this->resolveDefault($name, array_values($context));
        }

        return $values;
    }

    /**
     * @param  list<mixed>  $context
     */
    private function resolveDefault(string $name, array $context): mixed
    {
        $default = $this->defaults()[$name] ?? null;

        if (is_callable($default)) {
            return $default(...$context);
        }

        return $default;
    }
}
