<?php

namespace Tests;

use NiftyCo\Attachments\AttachmentsServiceProvider;
use Orchestra\Testbench;

abstract class TestCase extends Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            AttachmentsServiceProvider::class,
            Fixtures\ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Disable validation for tests to allow any file type
        $app['config']->set('attachments.validation', []);
    }
}
