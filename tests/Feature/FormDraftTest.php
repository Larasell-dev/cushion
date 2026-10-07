<?php

use Illuminate\Support\Facades\Cache;
use Larasell\FormDrafts\Tests\TestForm;
use Larasell\FormDrafts\Tests\TestUser;

beforeEach(function (): void {
    config(['form-drafts.driver' => 'cache']);
});

it('saves a partial draft and merges it with existing values', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);
    $key = "form-draft:Larasell\\FormDrafts\\Tests\\TestForm:{$user->id}";

    test()->actingAs($user)
        ->patch(route('forms.test.draft'), ['company' => 'ACME GmbH'])
        ->assertNoContent();

    expect(Cache::get($key))->company->toBe('ACME GmbH');

    test()->actingAs($user)
        ->patch(route('forms.test.draft'), ['city' => 'Berlin'])
        ->assertNoContent();

    $draft = Cache::get($key);

    expect($draft)->company->toBe('ACME GmbH')
        ->and($draft)->city->toBe('Berlin');
});

it('validates draft data against the form rules', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);

    test()->actingAs($user)
        ->patch(route('forms.test.draft'), ['city' => str_repeat('x', 256)])
        ->assertInvalid(['city']);
});

it('clears the draft', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);
    $key = "form-draft:Larasell\\FormDrafts\\Tests\\TestForm:{$user->id}";

    test()->actingAs($user)
        ->patch(route('forms.test.draft'), ['company' => 'ACME GmbH'])
        ->assertNoContent();

    test()->actingAs($user)
        ->delete(route('forms.test.draft.destroy'))
        ->assertNoContent();

    expect(Cache::get($key))->toBeNull();
});

it('hydrates with stored draft values over defaults', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);

    $form = new TestForm($user);

    expect($form->hydrate($user))->city->toBe('Vienna');

    $form->saveDraft(['city' => 'Berlin']);

    expect($form->hydrate($user))->city->toBe('Berlin');
});
