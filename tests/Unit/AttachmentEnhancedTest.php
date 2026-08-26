<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    Storage::fake('public');
});

it('generates readable file size', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'test.jpg',
        size: 1024,
        extname: 'jpg',
        mimeType: 'image/jpeg'
    );

    expect($attachment->readableSize())->toBe('1 KB');
});

it('formats bytes correctly', function () {
    $tests = [
        500 => '500 B',
        1024 => '1 KB',
        1048576 => '1 MB',
        1073741824 => '1 GB',
    ];

    foreach ($tests as $bytes => $expected) {
        $attachment = new Attachment(
            disk: 'public',
            name: 'test.jpg',
            size: $bytes,
            extname: 'jpg',
            mimeType: 'image/jpeg'
        );

        expect($attachment->readableSize())->toBe($expected);
    }
});

it('renders fixed decimals when a precision is given', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'test.jpg',
        size: 2 * 1024 * 1024,
        extname: 'jpg',
        mimeType: 'image/jpeg'
    );

    expect($attachment->readableSize())->toBe('2 MB')
        ->and($attachment->readableSize(2))->toBe('2.00 MB')
        ->and($attachment->readableSize(0))->toBe('2 MB');
});

it('handles null size gracefully', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'test.jpg',
        size: null,
        extname: 'jpg',
        mimeType: 'image/jpeg'
    );

    expect($attachment->readableSize())->toBe('Unknown');
});

it('can get file contents', function () {
    $file = UploadedFile::fake()->create('test.txt', 1, 'text/plain');
    Storage::disk('public')->put('test.txt', 'Hello World');

    $attachment = new Attachment(
        disk: 'public',
        name: 'test.txt',
        size: 11,
        extname: 'txt',
        mimeType: 'text/plain'
    );

    expect($attachment->contents())->toBe('Hello World');
});

it('returns empty string for non-existent file contents', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'non-existent.txt',
        size: 100,
        extname: 'txt',
        mimeType: 'text/plain'
    );

    // Storage::get() returns null for non-existent files
    $contents = $attachment->contents();
    expect($contents)->toBeString();
});

it('throws exception when getting contents without disk', function () {
    $attachment = new Attachment(
        disk: null,
        name: 'test.txt',
        size: 100,
        extname: 'txt',
        mimeType: 'text/plain'
    );

    expect(fn () => $attachment->contents())
        ->toThrow(RuntimeException::class, 'Cannot get contents');
});

it('can download file', function () {
    $file = UploadedFile::fake()->create('test.pdf');
    $attachment = Attachment::fromFile($file, 'public');

    $response = $attachment->download();

    expect($response)->toBeInstanceOf(StreamedResponse::class);
});

it('throws exception when downloading without disk', function () {
    $attachment = new Attachment(
        disk: null,
        name: 'test.pdf',
        size: 100,
        extname: 'pdf',
        mimeType: 'application/pdf'
    );

    expect(fn () => $attachment->download())
        ->toThrow(RuntimeException::class, 'Cannot download');
});

it('uses config defaults for disk and folder', function () {
    config(['attachments.disk' => 'public']);
    config(['attachments.folder' => 'uploads']);

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file);

    expect($attachment->disk())->toBe('public');
    expect($attachment->path())->toContain('uploads');
});
