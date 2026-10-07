<?php

use Larasell\Cushion\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure "uses()" in Pest lets you register reusable components.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');
