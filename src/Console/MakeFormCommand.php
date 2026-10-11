<?php

namespace Larasell\Cushion\Console;

use Illuminate\Console\GeneratorCommand;

class MakeFormCommand extends GeneratorCommand
{
    protected $name = 'make:form';

    protected $description = 'Create a new form class';

    protected $type = 'Form';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/form.php.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\'.match (true) {
            is_dir(app_path('Forms')) => 'Forms',
            default => 'Http\\Forms',
        };
    }
}
