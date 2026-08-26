<?php

namespace NiftyCo\Attachments;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

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
        $this->registerBlueprintMacros();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/attachments.php' => config_path('attachments.php'),
            ], 'attachments-config');
        }
    }

    /**
     * Register the schema blueprint macros for attachment columns.
     */
    protected function registerBlueprintMacros(): void
    {
        Blueprint::macro('attachment', function (string $column = 'attachment') {
            /** @var Blueprint $this */
            $this->json($column)->nullable();
        });

        Blueprint::macro('attachments', function (string $column = 'attachments') {
            /** @var Blueprint $this */
            $this->json($column)->nullable();
        });

        Blueprint::macro('dropAttachment', function (string $column = 'attachment') {
            /** @var Blueprint $this */
            $this->dropColumn($column);
        });

        Blueprint::macro('dropAttachments', function (string $column = 'attachments') {
            /** @var Blueprint $this */
            $this->dropColumn($column);
        });
    }
}
