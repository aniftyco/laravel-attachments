<?php

namespace Tests;

use Orchestra\Testbench;
use NiftyCo\Attachments\ServiceProvider;

abstract class TestCase extends Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [Fixtures\ServiceProvider::class, ServiceProvider::class];
    }
}
