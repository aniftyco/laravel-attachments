<?php

namespace NiftyCo\Attachments\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;

class AsAttachments implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Attachments
    {
        if (! isset($attributes[$key]) || $attributes[$key] === null) {
            return new Attachments([]);
        }

        $data = json_decode($value, true);

        if (! is_array($data) || empty($data)) {
            return new Attachments([]);
        }

        $attachments = [];

        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['disk'], $item['name'])) {
                continue;
            }

            try {
                $attachments[] = new Attachment(
                    disk: $item['disk'],
                    name: $item['name'],
                    size: $item['size'] ?? null,
                    extname: $item['extname'] ?? null,
                    mimeType: $item['mimeType'] ?? null,
                    metadata: $item['metadata'] ?? []
                );
            } catch (\Exception $e) {
                // Skip invalid attachments
                continue;
            }
        }

        return new Attachments($attachments);
    }

    public function set(Model $model, string $key, mixed $attachments, array $attributes): ?string
    {
        // Normalize the attachments first
        $newAttachments = null;
        if ($attachments instanceof Attachments) {
            $newAttachments = $attachments;
        }

        $oldAttachments = null;

        // Delete old attachments if replacement is enabled
        if (config('attachments.delete_on_replace', true)) {
            $oldAttachments = $this->deleteOldAttachments($model, $key, $newAttachments);
        }

        if ($newAttachments === null) {
            return json_encode([]);
        }

        // Dispatch events for new/updated attachments
        if (config('attachments.events.enabled', true)) {
            $this->dispatchEvents($model, $key, $newAttachments, $oldAttachments);
        }

        return $newAttachments->toJson();
    }

    /**
     * Delete the old attachments when they're being replaced.
     */
    protected function deleteOldAttachments(Model $model, string $key, ?Attachments $newAttachments): ?Attachments
    {
        // Only delete if the model exists (not a new model)
        if (! $model->exists) {
            return null;
        }

        // Get the raw original value from the database (before casting)
        $original = $model->getRawOriginal($key);

        if ($original === null) {
            return null;
        }

        // Build a set of new attachment paths for quick lookup
        $newPaths = [];
        if ($newAttachments !== null) {
            foreach ($newAttachments as $attachment) {
                $newPaths[$attachment->disk().':'.$attachment->path()] = true;
            }
        }

        try {
            // Parse the original JSON to get the old attachments
            $oldAttachments = $this->get($model, $key, $original, [$key => $original]);

            if ($oldAttachments instanceof Attachments && $oldAttachments->isNotEmpty()) {
                foreach ($oldAttachments as $attachment) {
                    // Safety check: don't delete if it's also in the new attachments
                    $pathKey = $attachment->disk().':'.$attachment->path();
                    if (isset($newPaths[$pathKey])) {
                        continue;
                    }

                    $attachment->delete();
                }

                return $oldAttachments;
            }
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to prevent the update
            if (\function_exists('logger')) {
                logger()->warning('Failed to delete old attachments during replacement', [
                    'model' => \get_class($model),
                    'attribute' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Dispatch events for attachment operations.
     *
     * @param  Attachments<int, Attachment>  $attachments
     * @param  Attachments<int, Attachment>|null  $oldAttachments
     */
    protected function dispatchEvents(Model $model, string $key, Attachments $attachments, ?Attachments $oldAttachments): void
    {
        // Event dispatching is disabled until event classes are created
        // TODO: Implement AttachmentCreated and AttachmentDeleted events

        // Suppress unused parameter warnings
        unset($model, $key, $attachments, $oldAttachments);
    }
}
