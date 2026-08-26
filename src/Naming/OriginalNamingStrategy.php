<?php

namespace NiftyCo\Attachments\Naming;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function NiftyCo\Attachments\sanitize_filename;

class OriginalNamingStrategy implements NamingStrategy
{
    public function __invoke(AttachmentContext $context): string
    {
        if ($context->originalName === null) {
            return (new RandomNamingStrategy)($context);
        }

        $name = sanitize_filename($context->originalName);
        $folder = trim($context->folder, '/');
        $path = $folder !== '' ? $folder.'/'.$name : $name;

        if (Storage::disk($context->disk)->exists($path)) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $basename = pathinfo($name, PATHINFO_FILENAME);
            $name = $basename.'-'.Str::random(8).($extension ? '.'.$extension : '');
            $path = $folder !== '' ? $folder.'/'.$name : $name;
        }

        return $path;
    }
}
