<?php

namespace NiftyCo\Attachments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
                'total_readable_size' => $this->formatBytes($totalSize),
            ],
        ];
    }

    /**
     * Format bytes to human-readable size.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
