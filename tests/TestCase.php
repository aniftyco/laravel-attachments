<?php

namespace Tests;

use Orchestra\Testbench;

abstract class TestCase extends Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [Fixtures\ServiceProvider::class];
    }
}
