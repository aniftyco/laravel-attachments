<?php

namespace NiftyCo\Attachments\Naming;

class AttachmentContext
{
    public function __construct(
        public readonly ?string $originalName,
        public readonly ?string $extension,
        public readonly ?string $mimeType,
        public readonly ?int $size,
        public readonly string $folder,
        public readonly string $disk,
    ) {}
}
