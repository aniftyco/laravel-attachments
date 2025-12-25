<?php

namespace NiftyCo\Attachments;

use Illuminate\Support\ServiceProvider;
use NiftyCo\Attachments\Database\AttachmentBlueprint;

class AttachmentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/attachments.php',
            'attachments'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register blueprint macros
        AttachmentBlueprint::register();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/attachments.php' => config_path('attachments.php'),
            ], 'attachments-config');
        }
    }
}
