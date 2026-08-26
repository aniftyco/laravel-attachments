<?php

namespace NiftyCo\Attachments;

use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;

if (! function_exists('NiftyCo\Attachments\default_disk')) {
    /**
     * Resolve the default attachment disk, falling back to the framework's
     * configured default filesystem when 'attachments.disk' is unset.
     */
    function default_disk(): string
    {
        return (string) (config('attachments.disk') ?? config('filesystems.default'));
    }
}

if (! function_exists('NiftyCo\Attachments\format_bytes')) {
    /**
     * Format a byte count as a human-readable size (e.g. "1.5 MB").
     *
     * With no precision the output is trimmed ("2 MB", "1.5 MB"); pass an integer
     * to render that many fixed decimal places ("2.00 MB", "2 MB" for 0).
     */
    function format_bytes(int|float $bytes, ?int $precision = null): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? (int) floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        $value = $precision === null
            ? (string) round($bytes, 2)
            : number_format($bytes, $precision, '.', '');

        return $value.' '.$units[$pow];
    }
}

if (! function_exists('NiftyCo\Attachments\attachment_casts')) {
    /**
     * Discover attachment-cast attributes from a model's cast map.
     *
     * @param  array<array-key, mixed>  $casts
     * @return array<string, bool> attribute => true when it's a collection cast
     */
    function attachment_casts(array $casts): array
    {
        $result = [];

        foreach ($casts as $attribute => $cast) {
            if (! is_string($cast) || ! is_string($attribute)) {
                continue;
            }

            if (is_a($cast, AsAttachments::class, true)) {
                $result[$attribute] = true;
            } elseif (is_a($cast, AsAttachment::class, true)) {
                $result[$attribute] = false;
            }
        }

        return $result;
    }
}

if (! function_exists('NiftyCo\Attachments\sanitize_filename')) {
    /**
     * Sanitize a filename for safe storage.
     */
    function sanitize_filename(string $filename): string
    {
        // Get the extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        // Remove any characters that aren't alphanumeric, dash, underscore, or space
        $basename = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $basename);

        // Replace multiple spaces with a single space
        $basename = preg_replace('/\s+/', ' ', $basename);

        // Replace spaces with dashes
        $basename = str_replace(' ', '-', $basename);

        // Remove multiple consecutive dashes
        $basename = preg_replace('/-+/', '-', $basename);

        // Trim dashes from start and end
        $basename = trim($basename, '-');

        // If basename is empty after sanitization, use a default
        if (empty($basename)) {
            $basename = 'file';
        }

        // Rebuild the filename
        return $extension ? $basename.'.'.$extension : $basename;
    }
}
