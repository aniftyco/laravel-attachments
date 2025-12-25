<?php

namespace NiftyCo\Attachments\Filament;

use Filament\Tables\Columns\ImageColumn;
use NiftyCo\Attachments\Attachment;

class AttachmentColumn extends ImageColumn
{
    /**
     * Configure the column to display an attachment.
     */
    public static function make(string $name): static
    {
        return parent::make($name)
            ->getStateUsing(function ($record) use ($name) {
                $attachment = data_get($record, $name);

                if ($attachment instanceof Attachment) {
                    return $attachment->url();
                }

                return null;
            })
            ->defaultImageUrl(function ($record) use ($name) {
                $attachment = data_get($record, $name);

                if ($attachment instanceof Attachment && ! $attachment->isImage()) {
                    // Return a default icon for non-image files
                    return null;
                }

                return null;
            });
    }

    /**
     * Display the attachment name instead of image.
     */
    public function asText(): static
    {
        return $this->formatStateUsing(function ($state, $record) {
            $attachment = data_get($record, $this->getName());

            if ($attachment instanceof Attachment) {
                return $attachment->name;
            }

            return null;
        });
    }

    /**
     * Display the attachment size.
     */
    public function asSize(): static
    {
        return $this->formatStateUsing(function ($state, $record) {
            $attachment = data_get($record, $this->getName());

            if ($attachment instanceof Attachment) {
                return $attachment->readableSize();
            }

            return null;
        });
    }

    /**
     * Make the attachment downloadable.
     */
    public function downloadable(): static
    {
        return $this->url(function ($record) {
            $attachment = data_get($record, $this->getName());

            if ($attachment instanceof Attachment) {
                return $attachment->url();
            }

            return null;
        }, shouldOpenInNewTab: true);
    }
}

