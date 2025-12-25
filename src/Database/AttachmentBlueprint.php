<?php

namespace NiftyCo\Attachments\Database;

use Illuminate\Database\Schema\Blueprint;

class AttachmentBlueprint
{
    /**
     * Register the blueprint macros.
     */
    public static function register(): void
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

