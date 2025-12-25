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
