<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Config\Repository;
use NiftyCo\Attachments\Naming\AttachmentContext;
use NiftyCo\Attachments\Naming\NamingStrategy;

class FixedNamingStrategy implements NamingStrategy
{
    public function __construct(private Repository $config) {}

    public function __invoke(AttachmentContext $context): string
    {
        // Proves the strategy resolves from the container with its dependency
        // injected, and that it can read configuration.
        $folder = trim($context->folder, '/');
        $prefix = $this->config->get('attachments.folder') ? 'custom' : 'fallback';
        $name = $prefix.'-fixed'.($context->extension ? '.'.$context->extension : '');

        return $folder !== '' ? $folder.'/'.$name : $name;
    }
}
