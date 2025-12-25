<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

beforeEach(function () {
    Storage::fake('public');
});

it('can detect image files', function () {
    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'files');

    expect($attachment->isImage())->toBeTrue();
    expect($attachment->isPdf())->toBeFalse();
    expect($attachment->isVideo())->toBeFalse();
    expect($attachment->isAudio())->toBeFalse();
});

it('can detect PDF files', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
    $attachment = Attachment::fromFile($file, 'public', 'files');

    expect($attachment->isPdf())->toBeTrue();
    expect($attachment->isDocument())->toBeTrue();
    expect($attachment->isImage())->toBeFalse();
});

it('can detect video files', function () {
    $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');
    $attachment = Attachment::fromFile($file, 'public', 'files');

    expect($attachment->isVideo())->toBeTrue();
    expect($attachment->isImage())->toBeFalse();
    expect($attachment->isAudio())->toBeFalse();
});

it('can detect audio files', function () {
    $file = UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg');
    $attachment = Attachment::fromFile($file, 'public', 'files');

    expect($attachment->isAudio())->toBeTrue();
    expect($attachment->isVideo())->toBeFalse();
    expect($attachment->isImage())->toBeFalse();
});

it('can detect document files', function () {
    $file = UploadedFile::fake()->create('test.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $attachment = Attachment::fromFile($file, 'public', 'files');

    expect($attachment->isDocument())->toBeTrue();
    expect($attachment->isImage())->toBeFalse();
});

it('sanitizes filenames correctly', function () {
    expect(Attachment::sanitizeFilename('Test File.pdf'))->toBe('Test-File.pdf');
    expect(Attachment::sanitizeFilename('file@#$%name.jpg'))->toBe('filename.jpg');
    expect(Attachment::sanitizeFilename('multiple   spaces.txt'))->toBe('multiple-spaces.txt');
    expect(Attachment::sanitizeFilename('---dashes---.doc'))->toBe('dashes.doc');
});

it('sanitizes filenames with special characters', function () {
    expect(Attachment::sanitizeFilename('file (1).pdf'))->toBe('file-1.pdf');
    expect(Attachment::sanitizeFilename('my_file-name.txt'))->toBe('my_file-name.txt');
    expect(Attachment::sanitizeFilename('UPPERCASE.PDF'))->toBe('UPPERCASE.PDF');
});

it('handles empty filename after sanitization', function () {
    expect(Attachment::sanitizeFilename('@#$%.pdf'))->toBe('file.pdf');
    expect(Attachment::sanitizeFilename('----.txt'))->toBe('file.txt');
});

it('handles filename without extension', function () {
    expect(Attachment::sanitizeFilename('test file'))->toBe('test-file');
    expect(Attachment::sanitizeFilename('@#$%'))->toBe('file');
});

it('preserves alphanumeric characters and allowed symbols', function () {
    expect(Attachment::sanitizeFilename('my-file_123.pdf'))->toBe('my-file_123.pdf');
    expect(Attachment::sanitizeFilename('test_file-v2.txt'))->toBe('test_file-v2.txt');
});

it('returns false for type checks when mimeType is null', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'test.txt',
        size: 100,
        extname: 'txt',
        mimeType: null
    );

    expect($attachment->isImage())->toBeFalse();
    expect($attachment->isVideo())->toBeFalse();
    expect($attachment->isAudio())->toBeFalse();
    expect($attachment->isDocument())->toBeFalse();
});

it('detects various image formats', function () {
    $formats = [
        ['png', 'image/png'],
        ['gif', 'image/gif'],
        ['webp', 'image/webp'],
        ['svg', 'image/svg+xml'],
    ];

    foreach ($formats as [$ext, $mime]) {
        $file = UploadedFile::fake()->create("test.$ext", 100, $mime);
        $attachment = Attachment::fromFile($file, 'public', 'files');
        expect($attachment->isImage())->toBeTrue("Failed for $ext");
    }
});

it('detects various document formats', function () {
    $formats = [
        ['doc', 'application/msword'],
        ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ['txt', 'text/plain'],
        ['csv', 'text/csv'],
    ];

    foreach ($formats as [$ext, $mime]) {
        $file = UploadedFile::fake()->create("test.$ext", 100, $mime);
        $attachment = Attachment::fromFile($file, 'public', 'files');
        expect($attachment->isDocument())->toBeTrue("Failed for $ext");
    }
});
