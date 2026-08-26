<?php

namespace NiftyCo\Attachments\Exceptions;

class StorageException extends AttachmentException
{
    public static function fileNotFound(string $path): self
    {
        return new self("File not found at path [{$path}].");
    }

    public static function deleteFailed(string $path, string $reason = ''): self
    {
        $message = "Failed to delete file at path [{$path}]";

        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    public static function uploadFailed(string $reason = '', ?\Throwable $previous = null): self
    {
        $message = 'Failed to upload file';

        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message, 0, $previous);
    }
}
