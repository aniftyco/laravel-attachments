<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Exceptions\StorageException;

beforeEach(function () {
    Storage::fake('public');
    config(['attachments.disk' => 'public']);
    config(['attachments.folder' => 'attachments']);
    config(['attachments.naming_strategy' => 'random']);
});

it('streams a remote url into the disk as-is', function () {
    $body = random_bytes(128);
    Http::fake(['*' => Http::response($body, 200, ['Content-Type' => 'image/png'])]);

    $attachment = Attachment::fromUrl('https://example.com/files/photo.png', 'public', 'remote');

    expect(Storage::disk('public')->get($attachment->path()))->toBe($body)
        ->and($attachment->size())->toBe(strlen($body))
        ->and($attachment->path())->toStartWith('remote/')
        ->and($attachment->path())->toEndWith('.png');
});

it('infers the mime type from the response content-type', function () {
    Http::fake(['*' => Http::response('hello', 200, ['Content-Type' => 'text/plain; charset=UTF-8'])]);

    $attachment = Attachment::fromUrl('https://example.com/notes.txt', 'public', 'remote');

    expect($attachment->mime())->toBe('text/plain')
        ->and($attachment->extension())->toBe('txt');
});

it('lets an explicit mime type override the content-type header', function () {
    Http::fake(['*' => Http::response('hello', 200, ['Content-Type' => 'text/plain'])]);

    $attachment = Attachment::fromUrl('https://example.com/notes.txt', 'public', 'remote', 'application/x-custom');

    expect($attachment->mime())->toBe('application/x-custom');
});

it('throws a StorageException when the fetch fails', function () {
    Http::fake(['*' => Http::response('nope', 404)]);

    expect(fn () => Attachment::fromUrl('https://example.com/missing.png'))
        ->toThrow(StorageException::class);
});

it('builds a collection from multiple urls', function () {
    Http::fake([
        '*/a.png' => Http::response('aaa', 200, ['Content-Type' => 'image/png']),
        '*/b.png' => Http::response('bbb', 200, ['Content-Type' => 'image/png']),
    ]);

    $collection = Attachments::fromUrls([
        'https://example.com/a.png',
        'https://example.com/b.png',
    ], 'public', 'remote');

    expect($collection)->toBeInstanceOf(Attachments::class)
        ->and($collection)->toHaveCount(2)
        ->and($collection->every(fn ($a) => Storage::disk('public')->exists($a->path())))->toBeTrue();
});
