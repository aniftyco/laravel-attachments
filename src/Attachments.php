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
     *
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     * @throws \NiftyCo\Attachments\Exceptions\ValidationException
     */
    public static function fromFiles(
        array $files,
        ?string $disk = null,
        ?string $folder = null,
        array|string|null $validate = null
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
     * @throws \NiftyCo\Attachments\Exceptions\StorageException
     * @throws \NiftyCo\Attachments\Exceptions\ValidationException
     */
    public function attach(
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null,
        array|string|null $validate = null
    ): static {
        $attachment = Attachment::fromFile($file, $disk, $folder, $validate);

        return $this->add($attachment);
    }

    /**
     * Alias for attach() for backwards compatibility.
     *
     * @deprecated Use attach() instead
     */
    public function addFromFile(
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null,
        array|string|null $validate = null
    ): static {
        return $this->attach($file, $disk, $folder, $validate);
    }
}
