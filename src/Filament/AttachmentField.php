<?php

namespace NiftyCo\Attachments\Filament;

use Filament\Forms\Components\FileUpload;
use NiftyCo\Attachments\Attachment;

class AttachmentField extends FileUpload
{
    /**
     * The disk to store attachments on.
     */
    protected ?string $attachmentDisk = null;

    /**
     * The folder to store attachments in.
     */
    protected ?string $attachmentFolder = null;

    /**
     * Set the disk for storing attachments.
     */
    public function attachmentDisk(?string $disk): static
    {
        $this->attachmentDisk = $disk;

        return $this->disk($disk ?? config('attachments.disk', 'public'));
    }

    /**
     * Set the folder for storing attachments.
     */
    public function attachmentFolder(?string $folder): static
    {
        $this->attachmentFolder = $folder;

        return $this->directory($folder ?? config('attachments.folder', 'attachments'));
    }

    /**
     * Configure the field to work with single attachments.
     */
    public static function make(string $name): static
    {
        return parent::make($name)
            ->disk(config('attachments.disk', 'public'))
            ->directory(config('attachments.folder', 'attachments'))
            ->dehydrateStateUsing(function ($state) {
                if (! $state) {
                    return null;
                }

                if ($state instanceof Attachment) {
                    return $state;
                }

                if (is_string($state)) {
                    // Handle file path from upload
                    return Attachment::fromPath(
                        $state,
                        config('attachments.disk', 'public'),
                        config('attachments.folder', 'attachments')
                    );
                }

                return $state;
            })
            ->formatStateUsing(function ($state) {
                if ($state instanceof Attachment) {
                    return $state->path();
                }

                return $state;
            });
    }

    /**
     * Configure the field to work with multiple attachments.
     */
    public static function multiple(string $name): static
    {
        return static::make($name)
            ->multiple()
            ->dehydrateStateUsing(function ($state) {
                if (! $state) {
                    return [];
                }

                return collect($state)->map(function ($item) {
                    if ($item instanceof Attachment) {
                        return $item;
                    }

                    if (is_string($item)) {
                        return Attachment::fromPath(
                            $item,
                            config('attachments.disk', 'public'),
                            config('attachments.folder', 'attachments')
                        );
                    }

                    return $item;
                })->all();
            })
            ->formatStateUsing(function ($state) {
                if (! $state) {
                    return [];
                }

                return collect($state)->map(function ($item) {
                    if ($item instanceof Attachment) {
                        return $item->path();
                    }

                    return $item;
                })->all();
            });
    }

    /**
     * Configure for image uploads only.
     */
    public function images(): static
    {
        return $this
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);
    }

    /**
     * Configure for document uploads only.
     */
    public function documents(): static
    {
        return $this->acceptedFileTypes([
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
