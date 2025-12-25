<?php

namespace NiftyCo\Attachments;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TAttachment of \NiftyCo\Attachments\Attachment
 *
 * @extends \Illuminate\Support\Collection<TKey, TAttachment>
 */
class Attachments extends Collection
{
    /**
     * Create a collection of attachments from multiple uploaded files.
     *
     * @param  array<UploadedFile>  $files
     * @param  mixed  $validate  Laravel validation rules (array, string, or ValidationRule object)
     *
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     * @throws \NiftyCo\Attachments\Exceptions\ValidationException
     */
    public static function fromFiles(
        array $files,
        ?string $disk = null,
        ?string $folder = null,
        mixed $validate = null
    ): static {
        $collection = new static;

        foreach ($files as $file) {
            $collection->attach($file, $disk, $folder, $validate);
        }

        return $collection;
    }

    /**
     * Attach a file to the collection.
     *
     * @param  mixed  $validate  Laravel validation rules (array, string, or ValidationRule object)
     *
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     * @throws \NiftyCo\Attachments\Exceptions\ValidationException
     */
    public function attach(
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null,
        mixed $validate = null
    ): static {
        $attachment = Attachment::fromFile($file, $disk, $folder, $validate);

        return $this->add($attachment);
    }

    /**
     * Delete all attachments in the collection from storage.
     *
     * @return bool True if all deletions were successful
     */
    public function delete(): bool
    {
        $success = true;

        foreach ($this->items as $attachment) {
            if (! $attachment->delete()) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move all attachments to a different disk and/or folder.
     *
     * @param  string  $disk  Target disk
     * @param  string|null  $folder  Target folder (optional)
     * @return static New collection with moved attachments
     *
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     */
    public function move(string $disk, ?string $folder = null): static
    {
        $moved = new static;

        foreach ($this->items as $attachment) {
            $moved->add($attachment->move($disk, $folder));
        }

        return $moved;
    }

    /**
     * Copy all attachments to a different disk and/or folder.
     *
     * @param  string  $disk  Target disk
     * @param  string|null  $folder  Target folder (optional)
     * @return static New collection with copied attachments
     *
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     */
    public function copy(string $disk, ?string $folder = null): static
    {
        $copied = new static;

        foreach ($this->items as $attachment) {
            $copied->add($attachment->duplicate($disk, $folder));
        }

        return $copied;
    }

    /**
     * Create a zip archive of all attachments.
     *
     * @param  string  $archiveName  Name of the archive file
     * @param  string|null  $disk  Disk to store the archive (defaults to first attachment's disk)
     * @param  string|null  $folder  Folder to store the archive
     * @return Attachment The created archive attachment
     *
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     */
    public function archive(string $archiveName, ?string $disk = null, ?string $folder = null): Attachment
    {
        if ($this->isEmpty()) {
            throw new \NiftyCo\Attachments\Exceptions\StorageException('Cannot create archive from empty collection');
        }

        // Use first attachment's disk if not specified
        $disk = $disk ?? $this->first()->disk();
        $folder = $folder ?? 'archives';

        // Create temporary zip file
        $tempZip = tempnam(sys_get_temp_dir(), 'attachments_');
        $zip = new \ZipArchive;

        if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \NiftyCo\Attachments\Exceptions\StorageException('Failed to create zip archive');
        }

        // Add each attachment to the zip
        foreach ($this->items as $attachment) {
            $contents = $attachment->contents();
            $name = $attachment->path();
            if ($name === null) {
                continue;
            }
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        // Create uploaded file from temp zip
        $uploadedFile = new UploadedFile(
            $tempZip,
            $archiveName,
            'application/zip',
            null,
            true
        );

        // Create attachment from the zip file
        $archive = Attachment::fromFile($uploadedFile, $disk, $folder);

        // Clean up temp file
        @unlink($tempZip);

        return $archive;
    }

    /**
     * Get total size of all attachments in bytes.
     */
    public function totalSize(): int
    {
        return $this->sum(fn (Attachment $attachment) => $attachment->size() ?? 0);
    }

    /**
     * Get human-readable total size of all attachments.
     */
    public function totalReadableSize(): string
    {
        $bytes = $this->totalSize();

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $index = (int) min($power, \count($units) - 1);

        return number_format($bytes / pow(1024, $power), 2, '.', ',').' '.$units[$index];
    }

    /**
     * Filter attachments by file type.
     *
     * @param  string  $type  Type to filter by (image, pdf, video, audio, document)
     */
    public function ofType(string $type): static
    {
        return $this->filter(function (Attachment $attachment) use ($type) {
            return match ($type) {
                'image' => $attachment->isImage(),
                'pdf' => $attachment->isPdf(),
                'video' => $attachment->isVideo(),
                'audio' => $attachment->isAudio(),
                'document' => $attachment->isDocument(),
                default => false,
            };
        });
    }
}
