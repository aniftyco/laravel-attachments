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
        // Old-file cleanup and events are handled by AttachmentObserver::saved(),
        // after the model is persisted — never here at assignment time.
        if (! $attachment instanceof Attachment) {
            return null;
        }

        return $attachment->toJson();
    }
}
