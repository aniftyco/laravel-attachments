<?php

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Exceptions\StorageException;

beforeEach(function () {
    Storage::fake('public');
    config(['attachments.disk' => 'public']);
    config(['attachments.folder' => 'attachments']);
});

it('writes raw content to storage as-is', function () {
    $content = random_bytes(64);

    $attachment = Attachment::fromContent($content, 'blob.bin', 'public', 'files');

    expect(Storage::disk('public')->get($attachment->path()))->toBe($content)
        ->and($attachment->size())->toBe(strlen($content));
});

it('honors the disk visibility default', function () {
    config(['filesystems.disks.public.visibility' => 'public']);

    $attachment = Attachment::fromContent('hello', 'note.txt', 'public', 'files');

    expect(Storage::disk('public')->getVisibility($attachment->path()))->toBe('public');
});

it('names the file via the strategy when the filename is null', function () {
    config(['attachments.naming_strategy' => 'random']);

    $attachment = Attachment::fromContent('hello', null, 'public', 'files');

    expect($attachment->path())->toStartWith('files/')
        ->and($attachment->extension())->toBeNull()
        ->and(Storage::disk('public')->exists($attachment->path()))->toBeTrue();
});

it('never throws for a missing filename', function () {
    $attachment = Attachment::fromContent('hello');

    expect($attachment)->toBeInstanceOf(Attachment::class);
});

it('infers the mime type from the filename extension', function () {
    $attachment = Attachment::fromContent('hello', 'note.txt', 'public', 'files');

    expect($attachment->mime())->toBe('text/plain');
});

it('lets an explicit mime type override inference', function () {
    $attachment = Attachment::fromContent('hello', 'note.txt', 'public', 'files', 'application/x-custom');

    expect($attachment->mime())->toBe('application/x-custom');
});

it('builds a collection from multiple contents', function () {
    $collection = Attachments::fromContents([
        ['content' => 'one', 'filename' => 'a.txt'],
        ['content' => 'two', 'filename' => 'b.txt', 'mimeType' => 'text/markdown'],
        ['content' => 'three'],
    ], 'public', 'files');

    expect($collection)->toBeInstanceOf(Attachments::class)
        ->and($collection)->toHaveCount(3)
        ->and($collection[0]->mime())->toBe('text/plain')
        ->and($collection[1]->mime())->toBe('text/markdown')
        ->and(Storage::disk('public')->get($collection[2]->path()))->toBe('three');
});

it('throws when a content item is missing its content key', function () {
    expect(fn () => Attachments::fromContents([['filename' => 'a.txt']]))
        ->toThrow(StorageException::class);
});

it('stores a plain Illuminate File through the naming strategy', function () {
    config(['attachments.naming_strategy' => 'random']);

    $path = tempnam(sys_get_temp_dir(), 'att').'.txt';
    file_put_contents($path, 'plain file contents');

    $attachment = Attachment::fromFile(new File($path), 'public', 'files');

    expect($attachment->path())->toStartWith('files/')
        ->and(Storage::disk('public')->exists($attachment->path()))->toBeTrue()
        ->and(Storage::disk('public')->get($attachment->path()))->toBe('plain file contents');

    @unlink($path);
});
