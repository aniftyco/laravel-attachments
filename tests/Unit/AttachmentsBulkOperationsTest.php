<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Storage::fake('backup');
});

it('can delete all attachments in collection', function () {
    $file1 = UploadedFile::fake()->image('photo1.jpg');
    $file2 = UploadedFile::fake()->image('photo2.jpg');
    $file3 = UploadedFile::fake()->image('photo3.jpg');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'photos'),
        Attachment::fromFile($file2, 'public', 'photos'),
        Attachment::fromFile($file3, 'public', 'photos'),
    ]);

    $paths = $attachments->map(fn ($a) => $a->path())->toArray();

    // Verify all files exist
    foreach ($paths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }

    // Delete all
    $result = $attachments->delete();

    expect($result)->toBeTrue();

    // Verify all files are deleted
    foreach ($paths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }
});

it('can move all attachments to different disk', function () {
    $file1 = UploadedFile::fake()->image('photo1.jpg');
    $file2 = UploadedFile::fake()->image('photo2.jpg');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'photos'),
        Attachment::fromFile($file2, 'public', 'photos'),
    ]);

    $originalPaths = $attachments->map(fn ($a) => $a->path())->toArray();

    // Move to s3
    $moved = $attachments->move('s3', 'archived');

    expect($moved)->toBeInstanceOf(Attachments::class)
        ->and($moved)->toHaveCount(2);

    // Original files should be deleted
    foreach ($originalPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }

    // New files should exist on s3
    foreach ($moved as $attachment) {
        expect($attachment->disk())->toBe('s3')
            ->and(Storage::disk('s3')->exists($attachment->path()))->toBeTrue();
    }
});

it('can duplicate all attachments to different disk', function () {
    $file1 = UploadedFile::fake()->create('doc1.pdf');
    $file2 = UploadedFile::fake()->create('doc2.pdf');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'documents'),
        Attachment::fromFile($file2, 'public', 'documents'),
    ]);

    $originalPaths = $attachments->map(fn ($a) => $a->path())->toArray();

    // Duplicate to backup
    $duplicated = $attachments->duplicate('backup', 'backups');

    expect($duplicated)->toBeInstanceOf(Attachments::class)
        ->and($duplicated)->toHaveCount(2);

    // Original files should still exist
    foreach ($originalPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }

    // Duplicated files should exist on backup disk
    foreach ($duplicated as $attachment) {
        expect($attachment->disk())->toBe('backup')
            ->and(Storage::disk('backup')->exists($attachment->path()))->toBeTrue();
    }
});

it('can calculate total size of attachments', function () {
    $file1 = UploadedFile::fake()->create('file1.txt', 100); // 100KB
    $file2 = UploadedFile::fake()->create('file2.txt', 200); // 200KB
    $file3 = UploadedFile::fake()->create('file3.txt', 300); // 300KB

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'files'),
        Attachment::fromFile($file2, 'public', 'files'),
        Attachment::fromFile($file3, 'public', 'files'),
    ]);

    $totalSize = $attachments->totalSize();

    expect($totalSize)->toBeGreaterThan(600000) // At least 600KB
        ->and($totalSize)->toBeLessThan(700000); // Less than 700KB (accounting for overhead)
});

it('can get human-readable total size', function () {
    $file1 = UploadedFile::fake()->create('file1.txt', 1024); // 1MB
    $file2 = UploadedFile::fake()->create('file2.txt', 2048); // 2MB

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'files'),
        Attachment::fromFile($file2, 'public', 'files'),
    ]);

    $readableSize = $attachments->totalReadableSize();

    expect($readableSize)->toContain('MB');
});

it('renders total size with fixed decimals when a precision is given', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->create('file1.txt', 1024), 'public', 'files'), // ~1 MB
        Attachment::fromFile(UploadedFile::fake()->create('file2.txt', 2048), 'public', 'files'), // ~2 MB
    ]);

    // Default output is unchanged (trimmed); precision forces fixed decimals.
    expect($attachments->totalReadableSize())->toContain('MB')
        ->and($attachments->totalReadableSize())->not->toContain('.00')
        ->and($attachments->totalReadableSize(2))->toContain('.00 MB');
});

it('can filter attachments by type - images', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->image('photo.jpg'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->create('doc.pdf'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->image('image.png'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->create('video.mp4'), 'public', 'files'),
    ]);

    $images = $attachments->ofType('image');

    expect($images)->toHaveCount(2)
        ->and($images->every(fn ($a) => $a->isImage()))->toBeTrue();
});

it('can filter attachments by type - pdfs', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->create('doc1.pdf'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->image('photo.jpg'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->create('doc2.pdf'), 'public', 'files'),
    ]);

    $pdfs = $attachments->ofType('pdf');

    expect($pdfs)->toHaveCount(2)
        ->and($pdfs->every(fn ($a) => $a->isPdf()))->toBeTrue();
});

it('can filter attachments by type - documents (pdf is its own category)', function () {
    $attachments = new Attachments([
        new Attachment('public', 'a.docx', 1, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        new Attachment('public', 'b.odt', 1, 'odt', 'application/vnd.oasis.opendocument.text'),
        new Attachment('public', 'c.pdf', 1, 'pdf', 'application/pdf'),
        new Attachment('public', 'd.jpg', 1, 'jpg', 'image/jpeg'),
    ]);

    expect($attachments->ofType('document'))->toHaveCount(2)
        ->and($attachments->ofType('pdf'))->toHaveCount(1);
});

it('can filter attachments by type - archives', function () {
    $attachments = new Attachments([
        new Attachment('public', 'a.zip', 1, 'zip', 'application/zip'),
        new Attachment('public', 'b.tar.gz', 1, 'tar.gz', 'application/x-compressed-tar'),
        new Attachment('public', 'c.pdf', 1, 'pdf', 'application/pdf'),
    ]);

    expect($attachments->ofType('archive'))->toHaveCount(2);
});

it('can filter attachments by type - text', function () {
    $attachments = new Attachments([
        new Attachment('public', 'a.md', 1, 'md', 'text/markdown'),
        new Attachment('public', 'b.json', 1, 'json', 'application/json'),
        new Attachment('public', 'c.jpg', 1, 'jpg', 'image/jpeg'),
    ]);

    expect($attachments->ofType('text'))->toHaveCount(2);
});

it('returns empty collection when filtering by unknown type', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->image('photo.jpg'), 'public', 'files'),
    ]);

    $filtered = $attachments->ofType('unknown');

    expect($filtered)->toBeEmpty();
});
