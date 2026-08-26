<?php

use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Exceptions\StorageException;

beforeEach(function () {
    Storage::fake('public');
    config(['attachments.disk' => 'public']);
    config(['attachments.folder' => 'attachments']);
    config(['attachments.naming_strategy' => 'random']);
});

function memoryStream(string $contents)
{
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $contents);
    rewind($stream);

    return $stream;
}

it('writes stream contents to the disk as-is', function () {
    $body = random_bytes(256);
    $stream = memoryStream($body);

    $attachment = Attachment::fromStream($stream, 'photo.png', 'public', 'streamed');

    expect(Storage::disk('public')->get($attachment->path()))->toBe($body)
        ->and($attachment->size())->toBe(strlen($body))
        ->and($attachment->path())->toStartWith('streamed/')
        ->and($attachment->path())->toEndWith('.png');

    fclose($stream);
});

it('does not close the caller stream', function () {
    $stream = memoryStream('hello');

    Attachment::fromStream($stream, 'notes.txt', 'public', 'streamed');

    expect(is_resource($stream))->toBeTrue();

    fclose($stream);
});

it('infers the mime type from the filename extension', function () {
    $stream = memoryStream('hello');

    $attachment = Attachment::fromStream($stream, 'notes.txt', 'public', 'streamed');

    expect($attachment->mime())->toBe('text/plain')
        ->and($attachment->extension())->toBe('txt');

    fclose($stream);
});

it('lets an explicit mime type override inference', function () {
    $stream = memoryStream('hello');

    $attachment = Attachment::fromStream($stream, 'notes.txt', 'public', 'streamed', 'application/x-custom');

    expect($attachment->mime())->toBe('application/x-custom');

    fclose($stream);
});

it('names the file via the strategy when the filename is null', function () {
    $stream = memoryStream('hello');

    $attachment = Attachment::fromStream($stream, null, 'public', 'streamed');

    expect($attachment->path())->toStartWith('streamed/')
        ->and($attachment->extension())->toBeNull()
        ->and(Storage::disk('public')->exists($attachment->path()))->toBeTrue();

    fclose($stream);
});

it('throws when the argument is not a stream resource', function () {
    expect(fn () => Attachment::fromStream('not a stream'))
        ->toThrow(StorageException::class);
});
