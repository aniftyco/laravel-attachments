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
        return [
            'name' => $this->name,
            'path' => $this->path(),
            'url' => $this->url(),
            'size' => $this->size,
            'readable_size' => $this->readableSize(),
            'mime' => $this->mime,
            'extension' => $this->extname,
            'disk' => $this->disk,
            'folder' => $this->folder,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
