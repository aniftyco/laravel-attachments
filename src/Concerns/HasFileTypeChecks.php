<?php

namespace NiftyCo\Attachments\Concerns;

trait HasFileTypeChecks
{
    /**
     * Check if the attachment is an image.
     */
    public function isImage(): bool
    {
        if ($this->mime() === null) {
            return false;
        }

        return str_starts_with($this->mime(), 'image/');
    }

    /**
     * Check if the attachment is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime() === 'application/pdf';
    }

    /**
     * Check if the attachment is a video.
     */
    public function isVideo(): bool
    {
        if ($this->mime() === null) {
            return false;
        }

        return str_starts_with($this->mime(), 'video/');
    }

    /**
     * Check if the attachment is an audio file.
     */
    public function isAudio(): bool
    {
        if ($this->mime() === null) {
            return false;
        }

        return str_starts_with($this->mime(), 'audio/');
    }

    /**
     * Check if the attachment is a document (PDF, Word, Excel, etc.).
     */
    public function isDocument(): bool
    {
        if ($this->mime() === null) {
            return false;
        }

        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime(), $documentMimes);
    }
}
