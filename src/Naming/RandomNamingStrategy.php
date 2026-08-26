<?php

namespace NiftyCo\Attachments\Naming;

use Illuminate\Support\Str;

class RandomNamingStrategy implements NamingStrategy
{
    public function __invoke(AttachmentContext $context): string
    {
        $name = Str::random(40);

        if ($context->extension) {
            $name .= '.'.$context->extension;
        }

        $folder = trim($context->folder, '/');

        return $folder !== '' ? $folder.'/'.$name : $name;
    }
}
