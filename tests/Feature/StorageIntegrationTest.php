<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Storage::fake('local');
});

it('can store file on public disk', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $attachment = Attachment::fromFile($file, 'public', 'images');

    expect($attachment->disk)->toBe('public')
        ->and(Storage::disk('public')->exists($attachment->path()))->toBeTrue()
        ->and($attachment->size)->toBeGreaterThan(0);
});

it('can store file on s3 disk', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500);

    $attachment = Attachment::fromFile($file, 's3', 'documents');

    expect($attachment->disk)->toBe('s3')
        ->and(Storage::disk('s3')->exists($attachment->path()))->toBeTrue()
        ->and($attachment->extname)->toBe('pdf');
});

it('can store file on local disk', function () {
    $file = UploadedFile::fake()->create('file.txt', 10);

    $attachment = Attachment::fromFile($file, 'local', 'files');

    expect($attachment->disk)->toBe('local')
        ->and(Storage::disk('local')->exists($attachment->path()))->toBeTrue();
});

it('can move attachment between disks', function () {
    $file = UploadedFile::fake()->image('photo.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'photos');
    $originalPath = $attachment->path();

    expect(Storage::disk('public')->exists($originalPath))->toBeTrue();

    $moved = $attachment->move('s3', 'archived');

    expect($moved->disk)->toBe('s3')
        ->and(Storage::disk('public')->exists($originalPath))->toBeFalse()
        ->and(Storage::disk('s3')->exists($moved->path()))->toBeTrue()
        ->and(basename($moved->name))->toBe(basename($attachment->name));
});

it('can duplicate attachment to different disk', function () {
    $file = UploadedFile::fake()->image('original.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'originals');
    $duplicate = $attachment->duplicate('s3', 'backups');

    expect($duplicate)->toBeInstanceOf(Attachment::class)
        ->and($duplicate->disk)->toBe('s3')
        ->and($duplicate->name)->not->toBe($attachment->name)
        ->and(Storage::disk('public')->exists($attachment->path()))->toBeTrue()
        ->and(Storage::disk('s3')->exists($duplicate->path()))->toBeTrue();
});

it('can delete file from storage', function () {
    $file = UploadedFile::fake()->image('delete-me.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'temp');
    $path = $attachment->path();

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $attachment->delete();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

it('can check if file exists in storage', function () {
    $file = UploadedFile::fake()->image('exists.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'files');

    expect($attachment->exists())->toBeTrue();

    $attachment->delete();

    expect($attachment->exists())->toBeFalse();
});

it('can get file contents from storage', function () {
    // Create a file with actual content
    $file = UploadedFile::fake()->createWithContent('content.txt', 'Hello World!');

    $attachment = Attachment::fromFile($file, 'public', 'files');
    $contents = $attachment->contents();

    expect($contents)->toBeString()
        ->and($contents)->toBe('Hello World!');
});

it('can get file url from storage', function () {
    $file = UploadedFile::fake()->image('url-test.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'images');
    $url = $attachment->url();

    expect($url)->toBeString()
        ->and($url)->toContain($attachment->name);
});

it('can get temporary url for private files', function () {
    $file = UploadedFile::fake()->image('private.jpg');

    $attachment = Attachment::fromFile($file, 's3', 'private');
    $tempUrl = $attachment->temporaryUrl(now()->addHour());

    expect($tempUrl)->toBeString()
        ->and($tempUrl)->not->toBeEmpty();
});

it('preserves file metadata across disk operations', function () {
    $file = UploadedFile::fake()->image('metadata.jpg', 200, 200);

    $attachment = Attachment::fromFile($file, 'public', 'images')
        ->withMetadata(['author' => 'John Doe', 'tags' => ['photo', 'test']]);

    $moved = $attachment->move('s3', 'archived');

    expect($moved->getMeta('author'))->toBe('John Doe')
        ->and($moved->getMeta('tags'))->toBe(['photo', 'test']);
});

it('handles large files correctly', function () {
    $file = UploadedFile::fake()->create('large.zip', 5000); // 5MB

    $attachment = Attachment::fromFile($file, 'public', 'uploads');

    expect($attachment->size)->toBeGreaterThan(5000000)
        ->and($attachment->readableSize())->toContain('MB')
        ->and(Storage::disk('public')->exists($attachment->path()))->toBeTrue();
});

it('can rename attachment file', function () {
    $file = UploadedFile::fake()->image('old-name.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'images');
    $oldPath = $attachment->path();

    $renamed = $attachment->rename('new-name');

    expect(basename($renamed->name))->toBe('new-name.jpg')
        ->and(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($renamed->path()))->toBeTrue();
});
