<?php

namespace Larasell\FormDrafts\Http\Controllers;

use Illuminate\Http\Request;
use Larasell\FormDrafts\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic draft endpoints for forms registered via the
 * `Route::form()` macro. The form class is embedded in the route's
 * defaults by the macro, never taken from request input.
 */
class FormDraftController
{
    public function update(Request $request): Response
    {
        $form = $this->resolveForm($request);

        $data = $request->validate($form->draftRules());

        $form->saveDraft($data);

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $this->resolveForm($request)->clearDraft();

        return response()->noContent();
    }

    /**
     * The form instance for the current route, built from the form
     * class embedded in the route's defaults by the macro.
     * The model defaults to the authenticated user.
     */
    protected function resolveForm(Request $request): Form
    {
        $formClass = $request->route()->parameter('form');

        if (! is_string($formClass) || ! is_a($formClass, Form::class, true)) {
            abort(404);
        }

        return new $formClass($request->user());
    }
}
