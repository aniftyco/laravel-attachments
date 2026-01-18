<?php

namespace NiftyCo\Attachments\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;

/**
 * @implements CastsAttributes<Attachment|null, Attachment|null>
 */
class AsAttachment implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Attachment
    {
        if (! isset($attributes[$key])) {
            return null;
        }

        $data = json_decode($value, true);

        if (! is_array($data) || empty($data)) {
            return null;
        }

        // Validate required fields exist
        if (! isset($data['disk'], $data['name'])) {
            return null;
        }

        try {
            return new Attachment(
                disk: $data['disk'],
                name: $data['name'],
                size: $data['size'] ?? null,
                extname: $data['extname'] ?? null,
                mimeType: $data['mimeType'] ?? null
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $attachment, array $attributes): ?string
    {
        // Normalize the attachment first
        $newAttachment = null;
        if ($attachment instanceof Attachment) {
            $newAttachment = $attachment;
        }

        // Delete old attachment if replacement is enabled
        if (config('attachments.delete_on_replace', true)) {
            $this->deleteOldAttachment($model, $key, $newAttachment);
        }

        if ($newAttachment === null) {
            return null;
        }

        return $newAttachment->toJson();
    }

    /**
     * Delete the old attachment when it's being replaced.
     */
    protected function deleteOldAttachment(Model $model, string $key, ?Attachment $newAttachment): void
    {
        // Only delete if the model exists (not a new model)
        if (! $model->exists) {
            return;
        }

        // Get the raw original value from the database (before casting)
        $original = $model->getRawOriginal($key);

        if ($original === null) {
            return;
        }

        try {
            // Parse the original JSON to get the old attachment
            $oldAttachment = $this->get($model, $key, $original, [$key => $original]);

            if ($oldAttachment instanceof Attachment) {
                // Safety check: don't delete if old and new point to the same file
                if ($newAttachment !== null
                    && $oldAttachment->path() === $newAttachment->path()
                    && $oldAttachment->disk() === $newAttachment->disk()) {
                    return;
                }

                $oldAttachment->delete();
            }
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to prevent the update
            if (\function_exists('logger')) {
                logger()->warning('Failed to delete old attachment during replacement', [
                    'model' => \get_class($model),
                    'attribute' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
