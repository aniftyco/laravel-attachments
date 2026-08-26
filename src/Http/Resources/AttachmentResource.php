<?php

namespace NiftyCo\Attachments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NiftyCo\Attachments\Attachment;

/**
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Attachment $attachment */
        $attachment = $this->resource;

        return [
            'path' => $attachment->path(),
            'url' => $attachment->url(),
            'size' => $attachment->size(),
            'readable_size' => $attachment->readableSize(),
            'mime' => $attachment->mime(),
            'extension' => $attachment->extension(),
            'disk' => $attachment->disk(),
            'folder' => $attachment->folder(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'type' => $this->getType(),
        ];
    }

    /**
     * Get the file type category.
     */
    protected function getType(): string
    {
        return match (true) {
            $this->isImage() => 'image',
            $this->isPdf() => 'pdf',
            $this->isVideo() => 'video',
            $this->isAudio() => 'audio',
            $this->isDocument() => 'document',
            default => 'file',
        };
    }
}
