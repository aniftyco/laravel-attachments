<?php

namespace NiftyCo\Attachments\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;

/**
 * @implements CastsAttributes<Attachments<int, Attachment>, Attachments<int, Attachment>|null>
 */
class AsAttachments implements CastsAttributes
{
    /**
     * @return Attachments<int, Attachment>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Attachments
    {
        if (! isset($attributes[$key])) {
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
        $oldAttachments = null;

        // Delete old attachments if replacement is enabled and new attachments are being set
        if (config('attachments.delete_on_replace', true)) {
            $oldAttachments = $this->deleteOldAttachments($model, $key);
        }

        if ($attachments === null) {
            return json_encode([]) ?: null;
        }

        if (! $attachments instanceof Attachments) {
            return json_encode([]) ?: null;
        }

        // Dispatch events for new/updated attachments
        if (config('attachments.events.enabled', true)) {
            $this->dispatchEvents($model, $key, $attachments, $oldAttachments);
        }

        return $attachments->toJson() ?: null;
    }

    /**
     * Delete the old attachments when they're being replaced.
     */
    protected function deleteOldAttachments(Model $model, string $key): ?Attachments
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

        try {
            // Parse the original JSON to get the old attachments
            $oldAttachments = $this->get($model, $key, $original, [$key => $original]);

            if ($oldAttachments instanceof Attachments && $oldAttachments->isNotEmpty()) {
                foreach ($oldAttachments as $attachment) {
                    $attachment->delete();

                    // Dispatch deleted event
                    if (config('attachments.events.enabled', true)) {
                        AttachmentDeleted::dispatch(
                            $attachment,
                            \get_class($model),
                            $model->getKey(),
                            $key
                        );
                    }
                }

                return $oldAttachments;
            }
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to prevent the update
            if (function_exists('logger')) {
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
     */
    protected function dispatchEvents(Model $model, string $key, Attachments $attachments, ?Attachments $oldAttachments): void
    {
        $modelClass = \get_class($model);
        $modelId = $model->getKey();

        // For simplicity, we dispatch created events for all new attachments
        // In a more sophisticated implementation, we could compare old vs new to detect updates
        foreach ($attachments as $attachment) {
            AttachmentCreated::dispatch($attachment, $modelClass, $modelId, $key);
        }
    }
}
