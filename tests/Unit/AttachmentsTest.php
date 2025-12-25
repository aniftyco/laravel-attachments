<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachments;

it('extends Illuminate\Support\Collection', function () {
    expect(new Attachments([]))->toBeInstanceOf(IlluminateCollection::class);
});

it('allows you to attach a file', function () {
    Storage::fake('public');

    $attachments = new Attachments;

    $attachments->attach(UploadedFile::fake()->image('image.jpg'));

    expect($attachments->count())->toBe(1);
    expect($attachments->first())->toBeInstanceOf(\NiftyCo\Attachments\Attachment::class);
});

it('can create collection from multiple files', function () {
    Storage::fake('public');

    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
        UploadedFile::fake()->image('photo3.jpg'),
    ];

    $attachments = Attachments::fromFiles($files);

    expect($attachments->count())->toBe(3);
    expect($attachments->every(fn ($item) => $item instanceof \NiftyCo\Attachments\Attachment))->toBeTrue();
});

it('applies validation to all files in fromFiles', function () {
    Storage::fake('public');

    $files = [
        UploadedFile::fake()->image('photo.jpg')->size(1024),
    ];

    $attachments = Attachments::fromFiles($files, validate: 'image|max:2048');

    expect($attachments->count())->toBe(1);
});

it('throws validation exception for invalid files in fromFiles', function () {
    Storage::fake('public');

    $files = [
        UploadedFile::fake()->create('document.pdf', 3000),
    ];

    Attachments::fromFiles($files, validate: 'image|max:2048');
})->throws(\NiftyCo\Attachments\Exceptions\ValidationException::class);
