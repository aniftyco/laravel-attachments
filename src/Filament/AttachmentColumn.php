<?php

namespace NiftyCo\Attachments\Filament;

use Filament\Tables\Columns\TextColumn;
use NiftyCo\Attachments\Attachment;

class AttachmentColumn extends TextColumn
{
    /**
     * Configure the column to display an attachment as a downloadable,
     * type-iconed label showing its human-readable size.
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->formatStateUsing(fn ($state): ?string => $state instanceof Attachment ? $state->readableSize() : null)
            ->icon(fn ($state): ?string => $state instanceof Attachment ? static::iconFor($state) : null)
            ->url(
                fn ($state): ?string => $state instanceof Attachment ? $state->url() : null,
                shouldOpenInNewTab: true
            );
    }

    /**
     * Map an attachment to a Heroicon name based on its type.
     */
    protected static function iconFor(Attachment $attachment): string
    {
        return match (true) {
            $attachment->isImage() => 'heroicon-o-photo',
            $attachment->isPdf() => 'heroicon-o-document-text',
            $attachment->isVideo() => 'heroicon-o-video-camera',
            $attachment->isAudio() => 'heroicon-o-musical-note',
            $attachment->isDocument() => 'heroicon-o-document',
            default => 'heroicon-o-paper-clip',
        };
    }
}
