<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

beforeEach(function () {
    Storage::fake('public');
});

it('transforms an attachment to the trimmed field set', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $array = (new AttachmentResource($attachment))->toArray(request());

    expect(array_keys($array))->toBe(['url', 'mime', 'size', 'extension', 'type'])
        ->and($array)->not->toHaveKeys(['readable_size', 'path', 'disk', 'folder'])
        ->and($array['mime'])->toBe('image/jpeg')
        ->and($array['extension'])->toBe('jpg')
        ->and($array['type'])->toBe('image')
        ->and($array['url'])->toContain('/storage/photos/')
        ->and($array['url'])->toContain('.jpg');
});

it('includes the type inside each attachment item', function () {
    $file = UploadedFile::fake()->create('document.pdf');
    $attachment = Attachment::fromFile($file, 'public', 'documents');

    $array = (new AttachmentResource($attachment))->toArray(request());

    expect($array)->toHaveKey('type')
        ->and($array['type'])->toBe('pdf');
});

it('transforms a collection of attachments via AttachmentResource::collection', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->image('photo1.jpg'), 'public', 'photos'),
        Attachment::fromFile(UploadedFile::fake()->image('photo2.jpg'), 'public', 'photos'),
        Attachment::fromFile(UploadedFile::fake()->image('photo3.jpg'), 'public', 'photos'),
    ]);

    $data = AttachmentResource::collection($attachments)->resolve(request());

    expect($data)->toHaveCount(3)
        ->and(array_keys($data[0]))->toBe(['url', 'mime', 'size', 'extension', 'type'])
        ->and($data[0]['type'])->toBe('image')
        ->and($data[0]['url'])->toContain('.jpg');
});
