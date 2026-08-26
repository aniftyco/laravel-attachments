<?php

namespace NiftyCo\Attachments;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;
use NiftyCo\Attachments\Concerns\HasFileTypeChecks;
use NiftyCo\Attachments\Exceptions\StorageException;
use NiftyCo\Attachments\Naming\AttachmentContext;
use NiftyCo\Attachments\Naming\NamingStrategyResolver;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;

/**
 * @phpstan-consistent-constructor
 */
class Attachment implements Jsonable, JsonSerializable
{
    use HasFileTypeChecks;

    private ?string $url = null;

    public static function fromFile(
        UploadedFile|File $file,
        ?string $disk = null,
        ?string $folder = null
    ): static {
        // Use config defaults
        $disk = $disk ?? config('attachments.disk');
        $folder = $folder ?? config('attachments.folder');

        try {
            $originalName = $file instanceof UploadedFile
                ? $file->getClientOriginalName()
                : $file->getFilename();

            $context = new AttachmentContext(
                originalName: $originalName,
                extension: $file->extension(),
                mimeType: $file->getMimeType(),
                size: $file->getSize(),
                folder: $folder,
                disk: $disk,
            );

            $path = (NamingStrategyResolver::resolve())($context);
            $directory = dirname($path);
            $directory = $directory === '.' ? '' : $directory;

            $visibility = config("filesystems.disks.{$disk}.visibility", 'private');
            $stored = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                basename($path),
                ['visibility' => $visibility]
            );

            if ($stored === false) {
                throw StorageException::uploadFailed('File storage returned false');
            }

            return new static(
                disk: $disk,
                name: $stored,
                size: $file->getSize(),
                extname: $file->extension(),
                mimeType: $file->getMimeType()
            );
        } catch (StorageException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw StorageException::uploadFailed($e->getMessage(), $e);
        }
    }

    /**
     * Create an attachment from raw file contents.
     *
     * The contents are written directly to storage as-is. The filename is used
     * only to derive the extension and feed the naming strategy — it is never
     * persisted.
     *
     * @throws StorageException
     */
    public static function fromContent(
        string $content,
        ?string $filename = null,
        ?string $disk = null,
        ?string $folder = null,
        ?string $mimeType = null
    ): static {
        $disk = $disk ?? config('attachments.disk');
        $folder = $folder ?? config('attachments.folder');

        $extension = $filename !== null
            ? (pathinfo($filename, PATHINFO_EXTENSION) ?: null)
            : null;

        if ($mimeType === null && $extension !== null) {
            $mimeType = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null;
        }

        $size = strlen($content);

        $context = new AttachmentContext(
            originalName: $filename,
            extension: $extension,
            mimeType: $mimeType,
            size: $size,
            folder: $folder,
            disk: $disk,
        );

        try {
            $path = (NamingStrategyResolver::resolve())($context);

            $visibility = config("filesystems.disks.{$disk}.visibility", 'private');
            $stored = Storage::disk($disk)->put($path, $content, ['visibility' => $visibility]);

            if ($stored === false) {
                throw StorageException::uploadFailed('File storage returned false');
            }

            return new static(
                disk: $disk,
                name: $path,
                size: $size,
                extname: $extension,
                mimeType: $mimeType
            );
        } catch (StorageException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw StorageException::uploadFailed($e->getMessage(), $e);
        }
    }

    /**
     * Create an attachment from a stream resource.
     *
     * The stream is written straight to storage without buffering it in memory
     * the way fromContent() does. The caller owns the stream and must close it —
     * writeStream() does not close it. The filename is used only to derive the
     * extension and feed the naming strategy; it is never persisted.
     *
     * @param  resource  $stream
     *
     * @throws StorageException
     */
    public static function fromStream(
        $stream,
        ?string $filename = null,
        ?string $disk = null,
        ?string $folder = null,
        ?string $mimeType = null
    ): static {
        if (! is_resource($stream)) {
            throw StorageException::uploadFailed('The provided value is not a stream resource.');
        }

        $disk = $disk ?? config('attachments.disk');
        $folder = $folder ?? config('attachments.folder');

        $extension = $filename !== null
            ? (pathinfo($filename, PATHINFO_EXTENSION) ?: null)
            : null;

        if ($mimeType === null && $extension !== null) {
            $mimeType = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null;
        }

        $context = new AttachmentContext(
            originalName: $filename,
            extension: $extension,
            mimeType: $mimeType,
            size: null,
            folder: $folder,
            disk: $disk,
        );

        try {
            $path = (NamingStrategyResolver::resolve())($context);

            $visibility = config("filesystems.disks.{$disk}.visibility", 'private');
            $stored = Storage::disk($disk)->writeStream($path, $stream, ['visibility' => $visibility]);

            if ($stored === false) {
                throw StorageException::uploadFailed('File storage returned false');
            }

            $size = Storage::disk($disk)->size($path);

            return new static(
                disk: $disk,
                name: $path,
                size: $size,
                extname: $extension,
                mimeType: $mimeType
            );
        } catch (StorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw StorageException::uploadFailed($e->getMessage(), $e);
        }
    }

    /**
     * Create an attachment by streaming a remote URL into the disk.
     *
     * The response body is streamed straight to storage without buffering the
     * whole payload in memory. The extension/filename are derived from the URL
     * path and fed to the naming strategy; the MIME type is taken from the
     * response Content-Type when not supplied.
     *
     * NOTE: This fetches whatever URL it is given. Guarding against SSRF
     * (validating/allow-listing the host) is the caller's responsibility.
     *
     * @throws StorageException
     */
    public static function fromUrl(
        string $url,
        ?string $disk = null,
        ?string $folder = null,
        ?string $mimeType = null
    ): static {
        $disk = $disk ?? config('attachments.disk');
        $folder = $folder ?? config('attachments.folder');

        $path = parse_url($url, PHP_URL_PATH);
        $filename = is_string($path) ? basename($path) : '';
        $filename = $filename !== '' ? $filename : null;
        $extension = $filename !== null ? (pathinfo($filename, PATHINFO_EXTENSION) ?: null) : null;

        try {
            $response = Http::withOptions(['stream' => true])->get($url);

            if (! $response->successful()) {
                throw StorageException::uploadFailed("Failed to fetch URL [{$url}]: HTTP {$response->status()}");
            }

            if ($mimeType === null) {
                $contentType = $response->header('Content-Type');
                $mimeType = $contentType !== '' ? (trim(explode(';', $contentType)[0]) ?: null) : null;
            }

            $context = new AttachmentContext(
                originalName: $filename,
                extension: $extension,
                mimeType: $mimeType,
                size: null,
                folder: $folder,
                disk: $disk,
            );

            $storagePath = (NamingStrategyResolver::resolve())($context);
            $visibility = config("filesystems.disks.{$disk}.visibility", 'private');

            $stream = $response->resource();
            $stored = Storage::disk($disk)->writeStream($storagePath, $stream, ['visibility' => $visibility]);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($stored === false) {
                throw StorageException::uploadFailed('File storage returned false');
            }

            $size = Storage::disk($disk)->size($storagePath);

            return new static(
                disk: $disk,
                name: $storagePath,
                size: $size,
                extname: $extension,
                mimeType: $mimeType
            );
        } catch (StorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw StorageException::uploadFailed($e->getMessage(), $e);
        }
    }

    /**
     * Create an attachment from an existing file path in storage.
     *
     * @param  string  $path  The file path in storage
     * @param  string|null  $disk  The storage disk (defaults to config)
     *
     * @throws StorageException
     */
    public static function fromPath(
        string $path,
        ?string $disk = null
    ): static {
        $disk = $disk ?? config('attachments.disk');

        if (! Storage::disk($disk)->exists($path)) {
            throw StorageException::fileNotFound($path);
        }

        try {
            $size = Storage::disk($disk)->size($path);
            $mimeType = Storage::disk($disk)->mimeType($path) ?: null;
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            return new static(
                disk: $disk,
                name: $path,
                size: $size,
                extname: $extension,
                mimeType: $mimeType
            );
        } catch (StorageException $e) {
            throw $e;
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
    ) {}

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
     * Get the folder/directory path.
     */
    public function folder(): ?string
    {
        if ($this->name === null) {
            return null;
        }

        $folder = dirname($this->name);

        return $folder === '.' ? null : $folder;
    }

    /**
     * Get the MIME type.
     */
    public function mime(): ?string
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
     * Get the disk URL for the attachment.
     *
     * Resolved lazily on first access; only a successful resolution is cached,
     * so a transient failure can be retried on a later call.
     */
    public function url(): string
    {
        if ($this->url !== null) {
            return $this->url;
        }

        if (! $this->disk || ! $this->name) {
            return '';
        }

        try {
            return $this->url = Storage::disk($this->disk)->url($this->name);
        } catch (\Exception $e) {
            return '';
        }
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
     * @param  int|null  $precision  Fixed decimal places; null trims the output
     */
    public function readableSize(?int $precision = null): string
    {
        if ($this->size === null) {
            return 'Unknown';
        }

        return format_bytes($this->size, $precision);
    }

    /**
     * Download the attachment.
     *
     * @param  string|null  $name  Custom download filename
     *
     * @throws \RuntimeException
     */
    public function download(?string $name = null): StreamedResponse
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

            if ($targetDisk === $this->disk) {
                // Same disk: a rename is a single filesystem operation.
                Storage::disk($this->disk)->move($this->name, $newPath);
            } else {
                // Cross-disk: copy the bytes over, then drop the original.
                $contents = Storage::disk($this->disk)->get($this->name);
                $visibility = config("filesystems.disks.{$targetDisk}.visibility", 'private');
                Storage::disk($targetDisk)->put($newPath, $contents, ['visibility' => $visibility]);
                Storage::disk($this->disk)->delete($this->name);
            }

            // Return new instance with updated location
            return new static(
                disk: $targetDisk,
                name: $newPath,
                size: $this->size,
                extname: $this->extname,
                mimeType: $this->mimeType
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
                mimeType: $this->mimeType
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
            $targetFolder = $targetFolder === '.' ? '' : $targetFolder;

            if ($name === null) {
                // Route the copy's name through the configured naming strategy,
                // exactly like every other fresh write.
                $context = new AttachmentContext(
                    originalName: basename($this->name),
                    extension: $this->extname,
                    mimeType: $this->mimeType,
                    size: $this->size,
                    folder: $targetFolder,
                    disk: $targetDisk,
                );

                $newPath = (NamingStrategyResolver::resolve())($context);
            } else {
                $newPath = $targetFolder === '' ? $name : $targetFolder.'/'.$name;
            }

            // Copy file
            $contents = Storage::disk($this->disk)->get($this->name);
            $visibility = config("filesystems.disks.{$targetDisk}.visibility", 'private');
            Storage::disk($targetDisk)->put($newPath, $contents, ['visibility' => $visibility]);

            // Return new instance
            return new static(
                disk: $targetDisk,
                name: $newPath,
                size: $this->size,
                extname: $this->extname,
                mimeType: $this->mimeType
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
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options) ?: '{}';
    }
}
