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
}
