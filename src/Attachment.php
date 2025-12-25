<?php

namespace NiftyCo\Attachments;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;
use NiftyCo\Attachments\Exceptions\StorageException;

class Attachment implements Jsonable, JsonSerializable
{
    private string $url;

    private array $metadata = [];

    public static function fromFile(
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null,
        array|string|null $validate = null,
        array $metadata = []
    ): static {
        // Validate file
        FileValidator::validate($file, $validate);

        // Use config defaults
        $disk = $disk ?? config('attachments.disk', config('filesystems.default'));
        $folder = $folder ?? config('attachments.folder', 'attachments');

        try {
            $path = $file->store($folder, $disk);

            if ($path === false) {
                throw StorageException::uploadFailed('File storage returned false');
            }

            $attachment = new static(
                disk: $disk,
                name: $path,
                size: $file->getSize(),
                extname: $file->extension(),
                mimeType: $file->getMimeType()
            );

            $attachment->metadata = $metadata;

            return $attachment;
        } catch (\Exception $e) {
            throw StorageException::uploadFailed($e->getMessage());
        }
    }

    public function __construct(
        private ?string $disk,
        private ?string $name,
        private ?int $size,
        private ?string $extname,
        private ?string $mimeType,
        array $metadata = []
    ) {
        $this->metadata = $metadata;
        if ($this->disk && $this->name) {
            try {
                $this->url = Storage::disk($this->disk)->url($this->name);
            } catch (\Exception $e) {
                $this->url = '';
            }
        } else {
            $this->url = '';
        }
    }

    /**
     * Check if the attachment file exists in storage.
     */
    public function exists(): bool
    {
        if (! $this->disk || ! $this->name) {
            return false;
        }

        try {
            return Storage::disk($this->disk)->exists($this->name);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete the attachment file from storage.
     */
    public function delete(): bool
    {
        if (! $this->disk || ! $this->name) {
            return false;
        }

        try {
            return Storage::disk($this->disk)->delete($this->name);
        } catch (\Exception $e) {
            throw StorageException::deleteFailed($this->name, $e->getMessage());
        }
    }

    /**
     * Get the storage disk name.
     */
    public function disk(): ?string
    {
        return $this->disk;
    }

    /**
     * Get the file name/path in storage.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Get the file path in storage (alias for name()).
     */
    public function path(): ?string
    {
        return $this->name;
    }

    /**
     * Get the file extension.
     */
    public function extname(): ?string
    {
        return $this->extname;
    }

    /**
     * Get the file extension (alias for extname()).
     */
    public function extension(): ?string
    {
        return $this->extname;
    }

    /**
     * Get the folder/directory path.
     */
    public function folder(): ?string
    {
        if ($this->name === null) {
            return null;
        }

        $dir = dirname($this->name);

        return $dir !== '.' ? $dir : null;
    }

    /**
     * Get the MIME type.
     */
    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Get the file size in bytes.
     */
    public function size(): ?int
    {
        return $this->size;
    }

    /**
     * Get the URL for the attachment.
     *
     * @param  bool  $full  Whether to return a full URL
     * @param  array  $parameters  Additional URL parameters
     * @param  bool|null  $secure  Whether to force HTTPS
     */
    public function url(bool $full = false, array $parameters = [], ?bool $secure = null): string
    {
        return $full ? url($this->url, $parameters, $secure) : $this->url;
    }

    /**
     * Generate a temporary URL for the attachment.
     *
     * @param  \DateTimeInterface|int  $expiration  Expiration time (minutes or DateTime)
     *
     * @throws \RuntimeException
     */
    public function temporaryUrl(\DateTimeInterface|int|null $expiration = null): string
    {
        if (! $this->disk || ! $this->name) {
            throw new \RuntimeException('Cannot generate temporary URL for attachment without disk or name.');
        }

        $expiration = $expiration ?? config('attachments.temporary_url_expiration', 60);

        if (is_int($expiration)) {
            $expiration = now()->addMinutes($expiration);
        }

        try {
            return Storage::disk($this->disk)->temporaryUrl($this->name, $expiration);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to generate temporary URL: {$e->getMessage()}");
        }
    }

    /**
     * Get a human-readable file size.
     *
     * @param  int  $precision  Number of decimal places
     */
    public function readableSize(int $precision = 2): string
    {
        if ($this->size === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($this->size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Download the attachment.
     *
     * @param  string|null  $name  Custom download filename
     *
     * @throws \RuntimeException
     */
    public function download(?string $name = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $this->disk || ! $this->name) {
            throw new \RuntimeException('Cannot download attachment without disk or name.');
        }

        try {
            return Storage::disk($this->disk)->download($this->name, $name);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to download file: {$e->getMessage()}");
        }
    }

    /**
     * Move the attachment to a different disk or folder.
     *
     * @param  string|null  $disk  Target disk (null to keep current)
     * @param  string|null  $folder  Target folder (null to keep current)
     * @return static New attachment instance with updated location
     *
     * @throws \RuntimeException
     */
    public function move(?string $disk = null, ?string $folder = null): static
    {
        if (! $this->disk || ! $this->name) {
            throw new \RuntimeException('Cannot move attachment without disk or name.');
        }

        $targetDisk = $disk ?? $this->disk;
        $targetFolder = $folder ?? dirname($this->name);

        // If nothing changed, return current instance
        if ($targetDisk === $this->disk && $targetFolder === dirname($this->name)) {
            return $this;
        }

        try {
            $filename = basename($this->name);
            $newPath = $targetFolder === '.' ? $filename : $targetFolder.'/'.$filename;

            // Copy to new location
            $contents = Storage::disk($this->disk)->get($this->name);
            Storage::disk($targetDisk)->put($newPath, $contents);

            // Delete old file
            Storage::disk($this->disk)->delete($this->name);

            // Return new instance with updated location
            return new static(
                disk: $targetDisk,
                name: $newPath,
                size: $this->size,
                extname: $this->extname,
                mimeType: $this->mimeType,
                metadata: $this->metadata
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to move file: {$e->getMessage()}");
        }
    }

    /**
     * Rename the attachment file.
     *
     * @param  string  $newName  New filename (without extension)
     * @param  bool  $keepExtension  Whether to keep the original extension
     * @return static New attachment instance with updated name
     *
     * @throws \RuntimeException
     */
    public function rename(string $newName, bool $keepExtension = true): static
    {
        if (! $this->disk || ! $this->name) {
            throw new \RuntimeException('Cannot rename attachment without disk or name.');
        }

        try {
            $folder = dirname($this->name);
            $extension = $keepExtension ? '.'.$this->extname : '';
            $newPath = ($folder === '.' ? '' : $folder.'/').$newName.$extension;

            // Rename file
            Storage::disk($this->disk)->move($this->name, $newPath);

            // Return new instance with updated name
            return new static(
                disk: $this->disk,
                name: $newPath,
                size: $this->size,
                extname: $this->extname,
                mimeType: $this->mimeType,
                metadata: $this->metadata
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to rename file: {$e->getMessage()}");
        }
    }

    /**
     * Create a duplicate of the attachment.
     *
     * @param  string|null  $disk  Target disk (null to use same disk)
     * @param  string|null  $folder  Target folder (null to use same folder)
     * @param  string|null  $name  Custom filename (null to auto-generate)
     * @return static New attachment instance
     *
     * @throws \RuntimeException
     */
    public function duplicate(?string $disk = null, ?string $folder = null, ?string $name = null): static
    {
        if (! $this->disk || ! $this->name) {
            throw new \RuntimeException('Cannot duplicate attachment without disk or name.');
        }

        try {
            $targetDisk = $disk ?? $this->disk;
            $targetFolder = $folder ?? dirname($this->name);

            // Generate new filename if not provided
            if ($name === null) {
                $basename = pathinfo($this->name, PATHINFO_FILENAME);
                $extension = $this->extname;
                $name = $basename.'_copy_'.time().'.'.$extension;
            }

            $newPath = $targetFolder === '.' ? $name : $targetFolder.'/'.$name;

            // Copy file
            $contents = Storage::disk($this->disk)->get($this->name);
            Storage::disk($targetDisk)->put($newPath, $contents);

            // Return new instance
            return new static(
                disk: $targetDisk,
                name: $newPath,
                size: $this->size,
                extname: $this->extname,
                mimeType: $this->mimeType,
                metadata: $this->metadata
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to duplicate file: {$e->getMessage()}");
        }
    }

    /**
     * Get the file contents.
     *
     * @throws \RuntimeException
     */
    public function contents(): string
    {
        if (! $this->disk || ! $this->name) {
            throw new \RuntimeException('Cannot get contents of attachment without disk or name.');
        }

        try {
            $contents = Storage::disk($this->disk)->get($this->name);

            return $contents ?? '';
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get file contents: {$e->getMessage()}");
        }
    }

    /**
     * Set metadata for the attachment (fluent).
     *
     * @param  array  $metadata  Metadata key-value pairs
     * @return static New attachment instance with metadata
     */
    public function withMetadata(array $metadata): static
    {
        $clone = clone $this;
        $clone->metadata = $metadata;

        return $clone;
    }

    /**
     * Get all metadata.
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a specific metadata value.
     *
     * @param  string  $key  Metadata key
     * @param  mixed  $default  Default value if key doesn't exist
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set a specific metadata value.
     *
     * @param  string  $key  Metadata key
     * @param  mixed  $value  Metadata value
     * @return static New attachment instance with updated metadata
     */
    public function setMeta(string $key, mixed $value): static
    {
        $clone = clone $this;
        $clone->metadata[$key] = $value;

        return $clone;
    }

    /**
     * Check if metadata key exists.
     *
     * @param  string  $key  Metadata key
     */
    public function hasMeta(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Remove a metadata key.
     *
     * @param  string  $key  Metadata key
     * @return static New attachment instance without the metadata key
     */
    public function removeMeta(string $key): static
    {
        $clone = clone $this;
        unset($clone->metadata[$key]);

        return $clone;
    }

    public function toArray(): array
    {
        $array = [
            'disk' => $this->disk,
            'name' => $this->name,
            'size' => $this->size,
            'extname' => $this->extname,
            'mimeType' => $this->mimeType,
        ];

        if (! empty($this->metadata)) {
            $array['metadata'] = $this->metadata;
        }

        return $array;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Check if the attachment is an image.
     */
    public function isImage(): bool
    {
        if ($this->mimeType === null) {
            return false;
        }

        return str_starts_with($this->mimeType, 'image/');
    }

    /**
     * Check if the attachment is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }

    /**
     * Check if the attachment is a video.
     */
    public function isVideo(): bool
    {
        if ($this->mimeType === null) {
            return false;
        }

        return str_starts_with($this->mimeType, 'video/');
    }

    /**
     * Check if the attachment is an audio file.
     */
    public function isAudio(): bool
    {
        if ($this->mimeType === null) {
            return false;
        }

        return str_starts_with($this->mimeType, 'audio/');
    }

    /**
     * Check if the attachment is a document (PDF, Word, Excel, etc.).
     */
    public function isDocument(): bool
    {
        if ($this->mimeType === null) {
            return false;
        }

        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mimeType, $documentMimes);
    }

    /**
     * Sanitize a filename for safe storage.
     */
    public static function sanitizeFilename(string $filename): string
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
