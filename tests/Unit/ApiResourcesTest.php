<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Http\Resources\AttachmentCollection;
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

beforeEach(function () {
    Storage::fake('public');
});

it('can transform attachment to resource', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
    $attachment = Attachment::fromFile($file, 'public', 'photos')
        ->withMeta('author', 'John Doe');

    $resource = new AttachmentResource($attachment);
    $array = $resource->toArray(request());

    expect($array)->toHaveKeys([
        'name',
        'path',
        'url',
        'size',
        'readable_size',
        'mime',
        'extension',
        'disk',
        'folder',
        'metadata',
        'created_at',
        'updated_at',
    ])
        ->and($array['name'])->toBe('photo.jpg')
        ->and($array['disk'])->toBe('public')
        ->and($array['folder'])->toBe('photos')
        ->and($array['extension'])->toBe('jpg')
        ->and($array['metadata'])->toBe(['author' => 'John Doe']);
});

it('includes file type in resource response', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $resource = new AttachmentResource($attachment);
    $with = $resource->with(request());

    expect($with)->toHaveKey('type')
        ->and($with['type'])->toBe('image');
});

it('correctly identifies pdf type', function () {
    $file = UploadedFile::fake()->create('document.pdf');
    $attachment = Attachment::fromFile($file, 'public', 'documents');

    $resource = new AttachmentResource($attachment);
    $with = $resource->with(request());

    expect($with['type'])->toBe('pdf');
});

it('can transform attachment collection to resource', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->image('photo1.jpg'), 'public', 'photos'),
        Attachment::fromFile(UploadedFile::fake()->image('photo2.jpg'), 'public', 'photos'),
        Attachment::fromFile(UploadedFile::fake()->image('photo3.jpg'), 'public', 'photos'),
    ]);

    $resource = new AttachmentCollection($attachments);
    $array = $resource->toArray(request());

    expect($array)->toHaveKeys(['data', 'meta'])
        ->and($array['data'])->toHaveCount(3)
        ->and($array['meta'])->toHaveKeys(['total', 'total_size', 'total_readable_size'])
        ->and($array['meta']['total'])->toBe(3);
});

it('includes total size in collection meta', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->create('file1.txt', 100), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->create('file2.txt', 200), 'public', 'files'),
    ]);

    $resource = new AttachmentCollection($attachments);
    $array = $resource->toArray(request());

    expect($array['meta']['total_size'])->toBeGreaterThan(0)
        ->and($array['meta']['total_readable_size'])->toBeString();
});

it('resource includes readable size', function () {
    $file = UploadedFile::fake()->create('file.txt', 1024); // 1MB
    $attachment = Attachment::fromFile($file, 'public', 'files');

    $resource = new AttachmentResource($attachment);
    $array = $resource->toArray(request());

    expect($array['readable_size'])->toContain('MB');
});

it('resource includes url', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $resource = new AttachmentResource($attachment);
    $array = $resource->toArray(request());

    expect($array['url'])->toBeString()
        ->and($array['url'])->toContain('photo.jpg');
});

it('resource handles null timestamps', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    // Timestamps are null by default
    $resource = new AttachmentResource($attachment);
    $array = $resource->toArray(request());

    expect($array['created_at'])->toBeNull()
        ->and($array['updated_at'])->toBeNull();
});

it('resource formats timestamps when present', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');
    $attachment->created_at = now();
    $attachment->updated_at = now();

    $resource = new AttachmentResource($attachment);
    $array = $resource->toArray(request());

    expect($array['created_at'])->toBeString()
        ->and($array['updated_at'])->toBeString();
});

