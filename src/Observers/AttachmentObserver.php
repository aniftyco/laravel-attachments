<?php

namespace NiftyCo\Attachments\Observers;

use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;

class AttachmentObserver
{
    /**
     * Handle the Model "deleting" event.
     *
     * This observer automatically deletes attachment files from storage
     * when a model is deleted, if auto_cleanup is enabled in config.
     */
    public function deleting(Model $model): void
    {
        // Check if auto-cleanup is enabled
        if (! config('attachments.auto_cleanup', true)) {
            return;
        }

        // Get all casts for the model
        $casts = $model->getCasts();

        foreach ($casts as $attribute => $cast) {
            // Check if this cast is an attachment cast
            if ($cast === AsAttachment::class || is_subclass_of($cast, AsAttachment::class)) {
                $this->deleteAttachment($model->getAttribute($attribute));
            } elseif ($cast === AsAttachments::class || is_subclass_of($cast, AsAttachments::class)) {
                $this->deleteAttachments($model->getAttribute($attribute));
            }
        }
    }

    /**
     * Delete a single attachment.
     */
    protected function deleteAttachment(?Attachment $attachment): void
    {
        if ($attachment === null) {
            return;
        }

        try {
            $attachment->delete();
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to prevent model deletion
            if (function_exists('logger')) {
                logger()->warning('Failed to delete attachment during model cleanup', [
                    'disk' => $attachment->disk(),
                    'path' => $attachment->path(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Delete multiple attachments.
     */
    protected function deleteAttachments(?Attachments $attachments): void
    {
        if ($attachments === null || $attachments->isEmpty()) {
            return;
        }

        foreach ($attachments as $attachment) {
            $this->deleteAttachment($attachment);
        }
    }
}
