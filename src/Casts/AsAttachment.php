<?php

namespace NiftyCo\Attachments\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Attachment;

class AsAttachment implements CastsAttributes
{

  public function get(Model $model, string $key, mixed $value, array $attributes): ?Attachment
  {
    if (!isset($attributes[$key])) {
      return null;
    }

    $attachment = (array) json_decode($value);

    if (empty($attachment)) {
      return null;
    }

    return new Attachment(...$attachment);
  }

  public function set(Model $model, string $key, mixed $attachment, array $attributes): ?string
  {
    if (!$attachment instanceof Attachment) {
      return null;
    }

    return $attachment->toJson();
  }
}
