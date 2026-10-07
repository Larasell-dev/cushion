<?php

namespace Larasell\Cushion\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route as RouteFacade;
use Larasell\Cushion\CushionServiceProvider;
use Larasell\Cushion\Form;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

abstract class TestCase extends TestbenchTestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [CushionServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    /**
     * @param  Application  $app
     */
    protected function defineRoutes($app): void
    {
        RouteFacade::middleware('auth')->group(function (): void {
            TestForm::add(TestForm::class);
        });
    }
}

class TestUser extends User
{
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = ['email', 'password'];
}

class TestForm extends Form
{
    public function fields(): array
    {
        return [
            'company' => 'string|max:255|required',
            'city' => 'string|max:255',
        ];
    }

    public function defaults(): array
    {
        return [
            'city' => 'Vienna',
        ];
    }
}
