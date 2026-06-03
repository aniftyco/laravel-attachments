<?php

namespace NiftyCo\Attachments\Concerns;

use NiftyCo\Attachments\Observers\AttachmentObserver;

trait HasAttachmentCleanup
{
    /**
     * Boot the HasAttachmentCleanup trait.
     *
     * Registers the AttachmentObserver to automatically clean up
     * attachment files when the model is deleted.
     */
    public static function bootHasAttachmentCleanup(): void
    {
        // Laravel 13 forbids `new static` during boot, which `observe()` does.
        // Register the event directly instead.
        static::deleting(function ($model): void {
            (new AttachmentObserver)->deleting($model);
        });
    }
}
