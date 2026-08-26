<?php

namespace NiftyCo\Attachments\Naming;

interface NamingStrategy
{
    /**
     * Resolve the final storage path (folder + filename) relative to the disk root.
     */
    public function __invoke(AttachmentContext $context): string;
}
