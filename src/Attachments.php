<?php

namespace NiftyCo\Attachments;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use NiftyCo\Attachments\Exceptions\StorageException;

/**
 * @extends Collection<int, Attachment>
 *
 * @phpstan-consistent-constructor
 */
class Attachments extends Collection
{
    /**
     * Create a collection of attachments from multiple uploaded files.
     *
     * @param  array<int, UploadedFile>  $files
     *
     * @throws StorageException
     */
    public static function fromFiles(
        array $files,
        ?string $disk = null,
        ?string $folder = null
    ): static {
        $collection = new static;

        foreach ($files as $file) {
            $collection->attach($file, $disk, $folder);
        }

        return $collection;
    }

    /**
     * Create a collection of attachments from multiple raw contents.
     *
     * Each item is an associative array with a required `content` key and
     * optional `filename` and `mimeType` keys.
     *
     * @param  array<int, array{content?: string, filename?: string|null, mimeType?: string|null}>  $items
     *
     * @throws StorageException
     */
    public static function fromContents(
        array $items,
        ?string $disk = null,
        ?string $folder = null
    ): static {
        $collection = new static;

        foreach ($items as $item) {
            if (! array_key_exists('content', $item)) {
                throw new StorageException('Each item passed to fromContents() must include a "content" key.');
            }

            $collection->add(Attachment::fromContent(
                $item['content'],
                $item['filename'] ?? null,
                $disk,
                $folder,
                $item['mimeType'] ?? null,
            ));
        }

        return $collection;
    }

    /**
     * Create a collection of attachments by streaming multiple remote URLs.
     *
     * See Attachment::fromUrl() — guarding against SSRF is the caller's
     * responsibility.
     *
     * @param  array<int, string>  $urls
     *
     * @throws StorageException
     */
    public static function fromUrls(
        array $urls,
        ?string $disk = null,
        ?string $folder = null
    ): static {
        $collection = new static;

        foreach ($urls as $url) {
            $collection->add(Attachment::fromUrl($url, $disk, $folder));
        }

        return $collection;
    }

    /**
     * Attach a file to the collection.
     *
     * @throws StorageException
     */
    public function attach(
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null
    ): static {
        $attachment = Attachment::fromFile($file, $disk, $folder);

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
     * @param  string|null  $disk  Target disk (null to keep each attachment's disk)
     * @param  string|null  $folder  Target folder (optional)
     * @return static New collection with moved attachments
     *
     * @throws StorageException
     */
    public function move(?string $disk = null, ?string $folder = null): static
    {
        $moved = new static;

        foreach ($this->items as $attachment) {
            $moved->add($attachment->move($disk, $folder));
        }

        return $moved;
    }

    /**
     * Duplicate all attachments to a different disk and/or folder.
     *
     * @param  string|null  $disk  Target disk (null to keep each attachment's disk)
     * @param  string|null  $folder  Target folder (optional)
     * @return static New collection with duplicated attachments
     *
     * @throws StorageException
     */
    public function duplicate(?string $disk = null, ?string $folder = null): static
    {
        $duplicated = new static;

        foreach ($this->items as $attachment) {
            $duplicated->add($attachment->duplicate($disk, $folder));
        }

        return $duplicated;
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
     *
     * @param  int|null  $precision  Fixed decimal places; null trims the output
     */
    public function totalReadableSize(?int $precision = null): string
    {
        return format_bytes($this->totalSize(), $precision);
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
