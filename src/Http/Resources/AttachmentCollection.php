<?php

namespace NiftyCo\Attachments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

use function NiftyCo\Attachments\format_bytes;

class AttachmentCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = AttachmentResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate totals from the original resource collection
        // $this->resource is the original Attachments collection before wrapping
        $totalSize = 0;
        foreach ($this->resource as $attachment) {
            $totalSize += $attachment->size() ?? 0;
        }

        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'total_size' => $totalSize,
                'total_readable_size' => format_bytes($totalSize),
            ],
        ];
    }
}
