<?php

namespace NiftyCo\Attachments;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TKey of array-key
 * @template TAttachment of \NiftyCo\Attachments\Attachment
 *
 * @extends \Illuminate\Support\Collection<TKey, TAttachment>
 */
class Collection extends BaseCollection
{
  public function addFromFile(UploadedFile $uploadedFile, ?string $disk = null, ?string $folder = 'attachments'): static
  {
    $attachment = Attachment::fromFile($uploadedFile, $disk, $folder);

    return $this->add($attachment);
  }
}
