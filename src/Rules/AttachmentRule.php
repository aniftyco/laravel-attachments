<?php

namespace NiftyCo\Attachments\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use NiftyCo\Attachments\FileValidator;

class AttachmentRule implements ValidationRule
{
    protected ?int $maxSize = null;

    protected ?array $allowedMimes = null;

    protected ?array $allowedExtensions = null;

    /**
     * Create a new rule instance.
     */
    public function __construct(
        ?int $maxSize = null,
        ?array $allowedMimes = null,
        ?array $allowedExtensions = null
    ) {
        $this->maxSize = $maxSize;
        $this->allowedMimes = $allowedMimes;
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * Set the maximum file size in bytes.
     */
    public function maxSize(int $bytes): self
    {
        $this->maxSize = $bytes;

        return $this;
    }

    /**
     * Set the maximum file size in kilobytes.
     */
    public function maxSizeKb(int $kilobytes): self
    {
        $this->maxSize = $kilobytes * 1024;

        return $this;
    }

    /**
     * Set the maximum file size in megabytes.
     */
    public function maxSizeMb(int $megabytes): self
    {
        $this->maxSize = $megabytes * 1024 * 1024;

        return $this;
    }

    /**
     * Set allowed MIME types.
     */
    public function mimes(array $mimes): self
    {
        $this->allowedMimes = $mimes;

        return $this;
    }

    /**
     * Set allowed file extensions.
     */
    public function extensions(array $extensions): self
    {
        $this->allowedExtensions = $extensions;

        return $this;
    }

    /**
     * Only allow image files.
     */
    public function images(): self
    {
        $this->allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];

        return $this;
    }

    /**
     * Only allow document files.
     */
    public function documents(): self
    {
        $this->allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a file.');

            return;
        }

        $rules = $this->buildValidationRules();

        try {
            FileValidator::validate($value, $rules);
        } catch (\Exception $e) {
            $fail($e->getMessage());
        }
    }

    /**
     * Build Laravel validation rules from the configured constraints.
     */
    protected function buildValidationRules(): array
    {
        $rules = ['file'];

        if ($this->maxSize !== null) {
            $rules[] = 'max:'.($this->maxSize / 1024); // Convert bytes to KB
        }

        if ($this->allowedMimes !== null) {
            $rules[] = 'mimes:'.implode(',', array_map(function ($mime) {
                // Convert MIME type to extension for Laravel's mimes rule
                return match ($mime) {
                    'image/jpeg' => 'jpg,jpeg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    'image/svg+xml' => 'svg',
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel' => 'xls',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                    default => '',
                };
            }, $this->allowedMimes));
        }

        if ($this->allowedExtensions !== null) {
            $rules[] = 'mimes:'.implode(',', $this->allowedExtensions);
        }

        return $rules;
    }

    /**
     * Static constructor for fluent usage.
     */
    public static function make(
        ?int $maxSize = null,
        ?array $allowedMimes = null,
        ?array $allowedExtensions = null
    ): self {
        return new self($maxSize, $allowedMimes, $allowedExtensions);
    }
}
