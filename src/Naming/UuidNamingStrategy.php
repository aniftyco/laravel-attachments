<?php

namespace NiftyCo\Attachments\Naming;

use Illuminate\Support\Str;

class UuidNamingStrategy implements NamingStrategy
{
    public function __invoke(AttachmentContext $context): string
    {
        $name = (string) Str::uuid();

        if ($context->extension) {
            $name .= '.'.$context->extension;
        }

        $folder = trim($context->folder, '/');

        return $folder !== '' ? $folder.'/'.$name : $name;
    }
}
