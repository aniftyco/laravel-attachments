<?php

namespace NiftyCo\Attachments\Concerns;

trait HasFileTypeChecks
{
    /**
     * Check if the attachment is an image.
     */
    public function isImage(): bool
    {
        return $this->mime() !== null && str_starts_with($this->mime(), 'image/');
    }

    /**
     * Check if the attachment is a video.
     */
    public function isVideo(): bool
    {
        return $this->mime() !== null && str_starts_with($this->mime(), 'video/');
    }

    /**
     * Check if the attachment is an audio file.
     */
    public function isAudio(): bool
    {
        return $this->mime() !== null && str_starts_with($this->mime(), 'audio/');
    }

    /**
     * Check if the attachment is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime() === 'application/pdf';
    }

    /**
     * Check if the attachment is an archive (zip, tar, gzip, 7z, rar, etc.).
     */
    public function isArchive(): bool
    {
        return in_array($this->mime(), [
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/x-tar',
            'application/x-gtar',
            'application/gzip',
            'application/x-gzip',
            'application/x-compressed-tar',
            'application/x-7z-compressed',
            'application/vnd.rar',
            'application/x-rar-compressed',
            'application/x-rar',
            'application/x-bzip2',
            'application/x-bz2',
            'application/bzip2',
            'application/x-bzip',
            'application/x-bzip1',
            'application/x-bzip-compressed-tar',
            'application/x-bzip2-compressed-tar',
            'application/x-xz',
            'application/x-xz-compressed-tar',
        ], true);
    }

    /**
     * Check if the attachment is a document (PDF, Office, OpenDocument, RTF, etc.).
     */
    public function isDocument(): bool
    {
        return in_array($this->mime(), [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/rtf',
            'text/plain',
            'text/csv',
        ], true);
    }

    /**
     * Check if the attachment is text (text/*, JSON, or XML).
     */
    public function isText(): bool
    {
        if ($this->mime() === null) {
            return false;
        }

        return str_starts_with($this->mime(), 'text/')
            || in_array($this->mime(), ['application/json', 'application/xml'], true);
    }

    /**
     * Resolve the attachment's category — the single source of truth.
     *
     * Priority order matters: pdf and archive win over the broader document
     * bucket, and document wins over plain text.
     *
     * @return 'image'|'video'|'audio'|'pdf'|'archive'|'document'|'text'|'other'
     */
    public function type(): string
    {
        return match (true) {
            $this->isImage() => 'image',
            $this->isVideo() => 'video',
            $this->isAudio() => 'audio',
            $this->isPdf() => 'pdf',
            $this->isArchive() => 'archive',
            $this->isDocument() => 'document',
            $this->isText() => 'text',
            default => 'other',
        };
    }
}
