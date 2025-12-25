<?php

namespace NiftyCo\Attachments;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use NiftyCo\Attachments\Exceptions\ValidationException;

class FileValidator
{
    /**
     * Validate an uploaded file using Laravel's validation rules.
     *
     * @param  array|string|null  $rules  Laravel validation rules (array or pipe-separated string)
     *
     * @throws \NiftyCo\Attachments\Exceptions\ValidationException
     */
    public static function validate(UploadedFile $file, array|string|null $rules = null): void
    {
        $rules = $rules ?? config('attachments.validation', []);

        // If rules is null or empty, skip validation
        if (empty($rules)) {
            return;
        }

        // Convert string rules to array if needed
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        // Create validator instance
        $validator = Validator::make(
            ['file' => $file],
            ['file' => $rules]
        );

        // If validation fails, throw our custom exception
        if ($validator->fails()) {
            $errors = $validator->errors()->get('file');
            $message = implode(' ', $errors);

            throw ValidationException::invalidFile($message);
        }
    }
}
