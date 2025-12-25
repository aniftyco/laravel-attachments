<?php

use Illuminate\Http\UploadedFile;
use NiftyCo\Attachments\Exceptions\ValidationException;
use NiftyCo\Attachments\FileValidator;

it('passes validation when no rules are configured', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    FileValidator::validate($file, []);

    expect(true)->toBeTrue(); // No exception thrown
});

it('validates file size with max rule', function () {
    $file = UploadedFile::fake()->create('large.pdf', 2048); // 2MB

    $rules = ['max:1024']; // 1MB max

    expect(fn () => FileValidator::validate($file, $rules))
        ->toThrow(ValidationException::class);
});

it('allows files under size limit', function () {
    $file = UploadedFile::fake()->create('small.pdf', 512); // 512KB

    $rules = ['max:1024']; // 1MB max

    FileValidator::validate($file, $rules);

    expect(true)->toBeTrue(); // No exception thrown
});

it('validates MIME type with mimetypes rule', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    $rules = ['mimetypes:image/jpeg,image/png'];

    expect(fn () => FileValidator::validate($file, $rules))
        ->toThrow(ValidationException::class);
});

it('allows valid MIME types', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $rules = ['mimetypes:image/jpeg,image/png'];

    FileValidator::validate($file, $rules);

    expect(true)->toBeTrue(); // No exception thrown
});

it('validates file extension with mimes rule', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    $rules = ['mimes:jpg,png'];

    expect(fn () => FileValidator::validate($file, $rules))
        ->toThrow(ValidationException::class);
});

it('allows valid file extensions', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $rules = ['mimes:jpg,png'];

    FileValidator::validate($file, $rules);

    expect(true)->toBeTrue(); // No exception thrown
});

it('validates with image rule', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    $rules = ['image'];

    expect(fn () => FileValidator::validate($file, $rules))
        ->toThrow(ValidationException::class);
});

it('allows images with image rule', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $rules = ['image'];

    FileValidator::validate($file, $rules);

    expect(true)->toBeTrue(); // No exception thrown
});

it('validates multiple rules together', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100); // Small image

    $rules = [
        'file',
        'image',
        'max:1024',
        'mimes:jpg,png',
    ];

    FileValidator::validate($file, $rules);

    expect(true)->toBeTrue(); // No exception thrown
});

it('accepts rules as pipe-separated string', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $rules = 'file|image|max:1024|mimes:jpg,png';

    FileValidator::validate($file, $rules);

    expect(true)->toBeTrue(); // No exception thrown
});

it('throws exception for invalid file with string rules', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    $rules = 'image|mimes:jpg,png';

    expect(fn () => FileValidator::validate($file, $rules))
        ->toThrow(ValidationException::class);
});

it('skips validation when rules is null', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    FileValidator::validate($file, null);

    expect(true)->toBeTrue(); // No exception thrown
});

it('skips validation when rules is empty array', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    FileValidator::validate($file, []);

    expect(true)->toBeTrue(); // No exception thrown
});
