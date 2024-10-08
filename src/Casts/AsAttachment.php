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

    $json = (array) json_decode($value);

    if (empty($json)) {
      return null;
    }

    return new Attachment(...$json);
  }

  public function set(Model $model, string $key, mixed $value, array $attributes): ?string
  {
    if (!$value instanceof Attachment) {
      return null;
    }

    return $value->toJson();
  }
}
