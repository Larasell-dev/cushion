<?php

use Illuminate\Support\Facades\Cache;
use Larasell\Cushion\Tests\GuestForm;
use Larasell\Cushion\Tests\TestForm;
use Larasell\Cushion\Tests\TestUser;

beforeEach(function (): void {
    config(['cushion.driver' => 'cache', 'session.driver' => 'array']);
});

it('scopes the draft to the owning model', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);

    $form = new TestForm($user);

    expect($form->draftOwnerKey())->toBe($user->id);
});

it('resolves the authenticated user when no model is given', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);
    test()->actingAs($user);

    $form = TestForm::get();

    expect($form->draftOwnerKey())->toBe($user->id);
});

it('binds the owner eagerly at construction', function (): void {
    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);
    $form = TestForm::get();

    test()->actingAs($user);

    expect($form->draftOwnerKey())->not->toBe($user->id);
});

it('resolves the guest session id as the owner for guests', function (): void {
    test()->patch(route('forms.guest.draft'), ['message' => 'Hello!'])
        ->assertNoContent();

    $sessionId = session()->getId();

    expect($sessionId)->not->toBeNull()
        ->and(Cache::get("form-draft:Larasell\\Cushion\\Tests\\GuestForm:{$sessionId}"))
        ->message->toBe('Hello!');
});

it('keys guest drafts per session, never shared', function (): void {
    test()->patch(route('forms.guest.draft'), ['message' => 'First guest'])
        ->assertNoContent();

    $firstSessionId = session()->getId();

    session()->regenerate();

    test()->patch(route('forms.guest.draft'), ['message' => 'Second guest'])
        ->assertNoContent();

    $secondSessionId = session()->getId();

    expect($firstSessionId)->not->toBe($secondSessionId)
        ->and(Cache::get("form-draft:Larasell\\Cushion\\Tests\\GuestForm:{$firstSessionId}"))
        ->message->toBe('First guest')
        ->and(Cache::get("form-draft:Larasell\\Cushion\\Tests\\GuestForm:{$secondSessionId}"))
        ->message->toBe('Second guest');
});

it('transfers a draft from one owner to another', function (): void {
    $oldUser = TestUser::create(['email' => 'old@example.com', 'password' => 'secret']);
    $newUser = TestUser::create(['email' => 'new@example.com', 'password' => 'secret']);

    $form = new TestForm($oldUser);
    $form->saveDraft(['company' => 'ACME GmbH']);

    $target = $form->transfer($newUser);

    expect($target)->draft()->company->toBe('ACME GmbH')
        ->and($form->draft())->toBe([]);
});

it('transfers an empty draft as a no-op', function (): void {
    $oldUser = TestUser::create(['email' => 'old@example.com', 'password' => 'secret']);
    $newUser = TestUser::create(['email' => 'new@example.com', 'password' => 'secret']);

    $target = (new TestForm($oldUser))->transfer($newUser);

    expect($target->draft())->toBe([]);
});

it('promotes a guest draft to the authenticated user', function (): void {
    test()->patch(route('forms.guest.draft'), ['message' => 'Half-written message'])
        ->assertNoContent();

    // Captured before login, like in the login controller.
    $guestForm = GuestForm::get();

    $user = TestUser::create(['email' => 'test@example.com', 'password' => 'secret']);
    test()->actingAs($user);

    $userForm = $guestForm->transfer($user);

    expect($userForm->draft())->message->toBe('Half-written message')
        ->and($guestForm->draft())->toBe([]);
});
