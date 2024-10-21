<?php

namespace NiftyCo\Attachments;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;

class Attachment implements Jsonable, JsonSerializable
{
  public string $url;

  public static function fromFile(UploadedFile $file, ?string $disk = null, ?string $folder = 'attachments'): static
  {
    $disk = $disk ?? config('filesystems.default');

    return new static(
      disk: $disk,
      name: $file->store($folder, $disk),
      size: $file->getSize(),
      extname: $file->extension(),
      mimeType: $file->getMimeType(),
    );
  }

  public function __construct(
    private ?string $disk,
    private ?string $name,
    private ?int $size,
    private ?string $extname,
    private ?string $mimeType,
  ) {
    $this->url = Storage::disk($this->disk)->url($this->name);
  }

  public function url($full = false, $parameters = [], $secure = null): string
  {
    return $full ? url($this->url, $parameters, $secure) : $this->url;
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

  public function toJson($options = 0)
  {
    return json_encode($this->jsonSerialize(), $options);
  }
}
