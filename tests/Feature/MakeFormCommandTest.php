<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('creates a new form class', function (): void {
    $formPath = app_path('Http/Forms/ContactForm.php');

    if (File::exists($formPath)) {
        unlink($formPath);
    }

    expect(File::exists($formPath))->toBeFalse();

    Artisan::call('make:form', ['name' => 'ContactForm']);

    expect(File::exists($formPath))->toBeTrue();

    $expected = <<<'CLASS'
        <?php

        namespace App\Http\Forms;

        use Larasell\Cushion\Form;

        final class ContactForm extends Form
        {
            /**
             * @return array<string, string|object|array<int, string|object>>
             */
            public function fields(): array
            {
                return [
                    //
                ];
            }
        }
        CLASS;

    expect(file_get_contents($formPath))->toBe($expected);

    unlink($formPath);
});

it('refuses to overwrite an existing form class without force', function (): void {
    $formPath = app_path('Http/Forms/ContactForm.php');

    if (! File::exists(dirname($formPath))) {
        File::makeDirectory(dirname($formPath), recursive: true);
    }

    File::put($formPath, '<?php // existing');

    $this->artisan('make:form', ['name' => 'ContactForm'])
        ->expectsOutputToContain('already exists')
        ->assertExitCode(0);

    expect(file_get_contents($formPath))->toBe('<?php // existing');

    unlink($formPath);
});
