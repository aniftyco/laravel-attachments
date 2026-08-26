<?php

namespace NiftyCo\Attachments\Naming;

use NiftyCo\Attachments\Exceptions\AttachmentException;

class NamingStrategyResolver
{
    /**
     * Resolve the configured naming strategy into an instance.
     *
     * @throws AttachmentException
     */
    public static function resolve(?string $strategy = null): NamingStrategy
    {
        $strategy = $strategy ?? config('attachments.naming_strategy', 'random');

        $class = match ($strategy) {
            'random' => RandomNamingStrategy::class,
            'uuid' => UuidNamingStrategy::class,
            'original' => OriginalNamingStrategy::class,
            default => $strategy,
        };

        if (! is_string($class) || ! class_exists($class)) {
            throw new AttachmentException("Invalid attachment naming strategy [{$strategy}].");
        }

        $instance = app($class);

        if (! $instance instanceof NamingStrategy) {
            throw new AttachmentException(
                "Attachment naming strategy [{$class}] must implement ".NamingStrategy::class.'.'
            );
        }

        return $instance;
    }
}
