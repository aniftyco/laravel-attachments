<?php

namespace NiftyCo\Attachments\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Collection;

class AsAttachments implements CastsAttributes
{
  public function get(Model $model, string $key, mixed $value, array $attributes): ?Collection
  {
    if (!isset($attributes[$key])) {
      return new Collection([]);
    }

    $attachments = json_decode($value);

    return new Collection(array_map(fn(mixed $item) => new Attachment(...(array) $item), $attachments));
  }

  public function set(Model $model, string $key, mixed $attachments, array $attributes): ?string
  {
    if (!$attachments instanceof Collection) {
      return (new Collection([]))->toJson();
    }

    return $attachments->toJson();
  }
}
