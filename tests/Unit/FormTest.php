<?php

use Larasell\FormDrafts\Tests\TestForm;

it('derives the draft key from the class basename', function (): void {
    expect(TestForm::draftKey())->toBe('test');
});

it('treats every field as optional in draft rules', function (): void {
    $form = new TestForm;

    expect($form->draftRules())->company->toBe(['nullable', 'string', 'max:255'])
        ->and($form->draftRules())->city->toBe(['nullable', 'string', 'max:255']);
});

it('keeps required rules for submit rules', function (): void {
    $form = new TestForm;

    expect($form->submitRules())->company->toBe(['string', 'max:255', 'required'])
        ->and($form->submitRules())->city->toBe(['nullable', 'string', 'max:255']);
});
