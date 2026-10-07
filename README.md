# Form drafts

This package helps you auto-saving forms, so users won't loose their
data when accidently closing a tab.

[See the full documentation](https://www.larasell.dev/docs/form-drafts)

## Installation

For this to work you need to install two packages. One for PHP the 
other for Javascript/Typescript.

```bash
composer require larasell-dev/form-drafts
npm install @larasell-dev/form-drafts
```

## Quickstart

### Defining forms

```php
use App\Models\User;
use Larasell\FormDrafts\Form;

final class CheckoutForm extends Form
{
    public function fields(): array
    {
        return [
            'company' => 'string|max:255',
            'first_name' => 'string|max:255|required',
            'last_name' => 'string|max:255|required',
            'street' => 'string|max:255|required',
            'city' => 'string|max:255|required',
            'postcode' => 'string|max:32|required',
            'country' => 'string|max:2|required',
        ];
    }
}
```

### Registering forms

Register the forms inside your `web.php` file:

```php
use App\Http\Forms\CheckoutForm;
use Larasell\FormDrafts\Form;

Form::add(CheckoutForm::class);

// Some other route
Route::get(/* ... */);
```

### Rendering forms

Now it's time to pass the form data from your controller
to the client:

```php
use App\Http\Forms\CheckoutForm;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

public function show(Request $request): Response
{
    $form = new CheckoutForm($request->user());

    return Inertia::render('checkout', [
        'billing' => $form->hydrate($request->user()),
    ]);
}
```

The last step is to fill your form with the data from
the draft:

```js
import { useFormDraft } from '@larasell-dev/form-drafts';

function Checkout({ billing }) {
  const { track, set, clear } = useFormDraft('checkout', billing);

  return (
    <form>
      {/* Uncontrolled inputs: spread `track` on */}
      <input type="text" name="company" {...track('company')} />
      <input type="text" name="first_name" {...track('first_name')} />

      {/* Widgets outside the change-event flow: call `set` */}
      <select
        defaultValue={billing.country}
        onChange={(event) => set('country', event.target.value)}
      />

      {/* Discard the stored draft */}
      <button type="button" onClick={() => clear()}>
        Clear draft
      </button>
    </form>
  );
}
```
