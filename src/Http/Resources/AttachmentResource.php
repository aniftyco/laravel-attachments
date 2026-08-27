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
            'url' => $this->url(),
            'mime' => $this->mime(),
            'size' => $this->size(),
            'extension' => $this->extension(),
            'type' => $this->type(),
        ];
    }
}
