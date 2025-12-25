<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use NiftyCo\Attachments\Rules\AttachmentRule;

it('validates file successfully with no restrictions', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => new AttachmentRule]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails validation when value is not a file', function () {
    $validator = Validator::make(
        ['file' => 'not a file'],
        ['file' => new AttachmentRule]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('file'))->toContain('must be a file');
});

it('validates file size limit', function () {
    $file = UploadedFile::fake()->create('test.pdf', 2048); // 2MB

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->maxSizeMb(1)]
    );

    expect($validator->fails())->toBeTrue();
});

it('passes validation when file is under size limit', function () {
    $file = UploadedFile::fake()->create('test.pdf', 512); // 512KB

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->maxSizeMb(1)]
    );

    expect($validator->passes())->toBeTrue();
});

it('validates allowed MIME types', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->mimes(['image/jpeg', 'image/png'])]
    );

    expect($validator->fails())->toBeTrue();
});

it('passes validation with correct MIME type', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->mimes(['image/jpeg', 'image/png'])]
    );

    expect($validator->passes())->toBeTrue();
});

it('validates allowed extensions', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100);

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->extensions(['jpg', 'png'])]
    );

    expect($validator->fails())->toBeTrue();
});

it('passes validation with correct extension', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->extensions(['jpg', 'png'])]
    );

    expect($validator->passes())->toBeTrue();
});

it('has images() helper method', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->images()]
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects non-images with images() helper', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->images()]
    );

    expect($validator->fails())->toBeTrue();
});

it('has documents() helper method', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->documents()]
    );

    expect($validator->passes())->toBeTrue();
});

it('can chain multiple constraints', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100); // Small image

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->images()->maxSizeMb(5)]
    );

    expect($validator->passes())->toBeTrue();
});

it('supports maxSizeKb method', function () {
    $file = UploadedFile::fake()->create('test.txt', 100); // 100KB

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->maxSizeKb(50)]
    );

    expect($validator->fails())->toBeTrue();
});

it('supports maxSize method in bytes', function () {
    $file = UploadedFile::fake()->create('test.txt', 1); // 1KB = 1024 bytes

    $validator = Validator::make(
        ['file' => $file],
        ['file' => AttachmentRule::make()->maxSize(512)]
    );

    expect($validator->fails())->toBeTrue();
});
