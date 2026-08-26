<?php

namespace NiftyCo\Attachments\Observers;

use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;

use function NiftyCo\Attachments\attachment_casts;

use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Events\AttachmentCreated;
use NiftyCo\Attachments\Events\AttachmentDeleted;
use NiftyCo\Attachments\Events\AttachmentUpdated;

class AttachmentObserver
{
    /**
     * Handle the Model "saved" event.
     *
     * Runs after the row is persisted, so the model key is available. Diffs the
     * original stored value against the current one per attachment attribute,
     * dispatches AttachmentCreated / AttachmentUpdated / AttachmentDeleted, and
     * (when delete_on_replace is enabled) purges the files of replaced/removed
     * attachments — post-persist, so a rollback never orphans a live record.
     */
    public function saved(Model $model): void
    {
        $modelClass = get_class($model);
        $modelId = $this->modelId($model);
        $deleteOnReplace = (bool) config('attachments.delete_on_replace', true);

        foreach ($this->attachmentCasts($model) as $attribute => $multiple) {
            $old = $model->getOriginal($attribute);
            $new = $model->getAttribute($attribute);

            if ($multiple) {
                $this->dispatchCollectionChanges(
                    $old instanceof Attachments ? $old : new Attachments,
                    $new instanceof Attachments ? $new : new Attachments,
                    $modelClass,
                    $modelId,
                    $attribute,
                    $deleteOnReplace
                );
            } else {
                $this->dispatchSingleChange(
                    $old instanceof Attachment ? $old : null,
                    $new instanceof Attachment ? $new : null,
                    $modelClass,
                    $modelId,
                    $attribute,
                    $deleteOnReplace
                );
            }
        }
    }

    /**
     * Handle the Model "deleting" event.
     *
     * Deletes attachment files from storage when a model is deleted (if
     * auto_cleanup is enabled) and dispatches an AttachmentDeleted event per
     * attachment.
     */
    public function deleting(Model $model): void
    {
        // A soft delete doesn't remove the row's files — the record (and its
        // attachments) can still be restored. Only purge/dispatch on a real
        // delete: a force delete, or a model that doesn't use SoftDeletes.
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }

        $cleanup = config('attachments.auto_cleanup', true);

        // Capture identity up front, before any attachment cast is hydrated.
        $modelClass = get_class($model);
        $modelId = $this->modelId($model);

        foreach ($this->attachmentCasts($model) as $attribute => $multiple) {
            $value = $model->getAttribute($attribute);

            if ($multiple) {
                $this->handleAttachments($value instanceof Attachments ? $value : null, $cleanup, $modelClass, $modelId, $attribute);
            } else {
                $this->handleAttachment($value instanceof Attachment ? $value : null, $cleanup, $modelClass, $modelId, $attribute);
            }
        }
    }

    /**
     * Discover the attachment-cast attributes on the model.
     *
     * @return array<string, bool> attribute => true when it's a collection cast
     */
    protected function attachmentCasts(Model $model): array
    {
        return attachment_casts($model->getCasts());
    }

    /**
     * Dispatch a create/update/delete event for a single attachment attribute,
     * purging the replaced/removed file when delete_on_replace is enabled.
     */
    protected function dispatchSingleChange(?Attachment $old, ?Attachment $new, string $modelClass, ?string $modelId, string $attribute, bool $deleteOnReplace): void
    {
        if ($new === null) {
            if ($old !== null) {
                if ($deleteOnReplace) {
                    $this->deleteQuietly($old);
                }

                event(new AttachmentDeleted($old, $modelClass, $modelId, $attribute));
            }

            return;
        }

        if ($old === null) {
            event(new AttachmentCreated($new, $modelClass, $modelId, $attribute));

            return;
        }

        if ($this->storageKey($old) !== $this->storageKey($new)) {
            if ($deleteOnReplace) {
                $this->deleteQuietly($old);
            }

            event(new AttachmentUpdated($new, $old, $modelClass, $modelId, $attribute));
        }
    }

    /**
     * Dispatch create/delete events for a collection attribute by diffing the
     * old and new sets: added items are created, removed items are deleted (and
     * their files purged when delete_on_replace is enabled).
     */
    protected function dispatchCollectionChanges(Attachments $old, Attachments $new, string $modelClass, ?string $modelId, string $attribute, bool $deleteOnReplace): void
    {
        $oldKeys = [];
        foreach ($old as $attachment) {
            $oldKeys[$this->storageKey($attachment)] = true;
        }

        $newKeys = [];
        foreach ($new as $attachment) {
            $newKeys[$this->storageKey($attachment)] = true;

            if (! isset($oldKeys[$this->storageKey($attachment)])) {
                event(new AttachmentCreated($attachment, $modelClass, $modelId, $attribute));
            }
        }

        foreach ($old as $attachment) {
            if (! isset($newKeys[$this->storageKey($attachment)])) {
                if ($deleteOnReplace) {
                    $this->deleteQuietly($attachment);
                }

                event(new AttachmentDeleted($attachment, $modelClass, $modelId, $attribute));
            }
        }
    }

    /**
     * Clean up and dispatch a deletion event for a single attachment.
     */
    protected function handleAttachment(?Attachment $attachment, bool $cleanup, string $modelClass, ?string $modelId, string $attribute): void
    {
        if ($attachment === null) {
            return;
        }

        if ($cleanup) {
            $this->deleteQuietly($attachment);
        }

        event(new AttachmentDeleted($attachment, $modelClass, $modelId, $attribute));
    }

    /**
     * Clean up and dispatch deletion events for multiple attachments.
     */
    protected function handleAttachments(?Attachments $attachments, bool $cleanup, string $modelClass, ?string $modelId, string $attribute): void
    {
        if ($attachments === null || $attachments->isEmpty()) {
            return;
        }

        foreach ($attachments as $attachment) {
            $this->handleAttachment($attachment, $cleanup, $modelClass, $modelId, $attribute);
        }
    }

    /**
     * Delete an attachment's file, swallowing and logging any failure so a
     * storage hiccup never blocks the save or the model deletion.
     */
    protected function deleteQuietly(Attachment $attachment): void
    {
        try {
            $attachment->delete();
        } catch (\Exception $e) {
            if (function_exists('logger')) {
                logger()->warning('Failed to delete attachment file', [
                    'disk' => $attachment->disk(),
                    'path' => $attachment->path(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build the storage identity for an attachment.
     */
    protected function storageKey(Attachment $attachment): string
    {
        return $attachment->disk().':'.$attachment->path();
    }

    /**
     * Resolve the model key as a string, if any.
     */
    protected function modelId(Model $model): ?string
    {
        $key = $model->getKey();

        return $key === null ? null : (string) $key;
    }
}
