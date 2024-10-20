<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Collection;
use Illuminate\Support\Collection as IlluminateCollection;

it('extends Illuminate\Support\Collection', function () {
    expect(new Collection([]))->toBeInstanceOf(IlluminateCollection::class);
});

it('allows you to add an attachment from a file', function () {
    Storage::fake('public');

    $collection = new Collection();

    $collection->addFromFile(UploadedFile::fake()->image('image.jpg'));

    expect($collection->count())->toBe(1);
    expect($collection->first())->toBeInstanceOf(\NiftyCo\Attachments\Attachment::class);
});
