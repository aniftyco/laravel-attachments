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

    public static function fromFile(
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null,
        array|string|null $validate = null
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

            return new static(
                disk: $disk,
                name: $path,
                size: $file->getSize(),
                extname: $file->extension(),
                mimeType: $file->getMimeType()
            );
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
    ) {
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
     * Get the file path in storage.
     */
    public function path(): ?string
    {
        return $this->name;
    }

    /**
     * Get the file extension.
     */
    public function extension(): ?string
    {
        return $this->extname;
    }

    /**
     * Get the MIME type.
     */
    public function mime(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Alias for mime() for backwards compatibility.
     *
     * @deprecated Use mime() instead
     */
    public function mimeType(): ?string
    {
        return $this->mime();
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
    public function tempUrl(\DateTimeInterface|int|null $expiration = null): string
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
     * Alias for tempUrl() for backwards compatibility.
     *
     * @deprecated Use tempUrl() instead
     */
    public function temporaryUrl(\DateTimeInterface|int|null $expiration = null): string
    {
        return $this->tempUrl($expiration);
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
     * Alias for readableSize() for backwards compatibility.
     *
     * @deprecated Use readableSize() instead
     */
    public function humanReadableSize(int $precision = 2): string
    {
        return $this->readableSize($precision);
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

    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'name' => $this->name,
            'size' => $this->size,
            'extname' => $this->extname,
            'mimeType' => $this->mimeType,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
