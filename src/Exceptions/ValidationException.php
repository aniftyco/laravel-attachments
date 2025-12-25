<?php

namespace NiftyCo\Attachments\Exceptions;

class ValidationException extends AttachmentException
{
    public static function fileTooLarge(int $size, int $maxSize): self
    {
        $sizeInMb = round($size / 1024 / 1024, 2);
        $maxSizeInMb = round($maxSize / 1024, 2);

        return new self(
            "File size ({$sizeInMb}MB) exceeds maximum allowed size ({$maxSizeInMb}MB)."
        );
    }

    public static function invalidMimeType(string $mimeType, array $allowedTypes): self
    {
        $allowed = implode(', ', $allowedTypes);

        return new self(
            "File MIME type [{$mimeType}] is not allowed. Allowed types: {$allowed}"
        );
    }

    public static function invalidExtension(string $extension, array $allowedExtensions): self
    {
        $allowed = implode(', ', $allowedExtensions);

        return new self(
            "File extension [{$extension}] is not allowed. Allowed extensions: {$allowed}"
        );
    }

    public static function invalidFile(string $reason = ''): self
    {
        $message = 'Invalid file';

        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }
}
