<?php

namespace NiftyCo\Attachments\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;

/**
 * @implements CastsAttributes<Attachments, Attachments>
 */
class AsAttachments implements CastsAttributes
{
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
                    mimeType: $item['mimeType'] ?? null
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
        // Old-file cleanup and events are handled by AttachmentObserver::saved(),
        // after the model is persisted — never here at assignment time.
        if (! $attachments instanceof Attachments) {
            return '[]';
        }

        return $attachments->toJson();
    }
}
