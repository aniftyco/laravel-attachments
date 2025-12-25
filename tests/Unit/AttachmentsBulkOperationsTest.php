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

it('can copy all attachments to different disk', function () {
    $file1 = UploadedFile::fake()->create('doc1.pdf');
    $file2 = UploadedFile::fake()->create('doc2.pdf');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'documents'),
        Attachment::fromFile($file2, 'public', 'documents'),
    ]);

    $originalPaths = $attachments->map(fn ($a) => $a->path())->toArray();

    // Copy to backup
    $copied = $attachments->copy('backup', 'backups');

    expect($copied)->toBeInstanceOf(Attachments::class)
        ->and($copied)->toHaveCount(2);

    // Original files should still exist
    foreach ($originalPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }

    // Copied files should exist on backup disk
    foreach ($copied as $attachment) {
        expect($attachment->disk())->toBe('backup')
            ->and(Storage::disk('backup')->exists($attachment->path()))->toBeTrue();
    }
});

it('can create zip archive from attachments', function () {
    $file1 = UploadedFile::fake()->create('doc1.pdf', 10);
    $file2 = UploadedFile::fake()->create('doc2.pdf', 20);
    $file3 = UploadedFile::fake()->create('doc3.pdf', 30);

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'files'),
        Attachment::fromFile($file2, 'public', 'files'),
        Attachment::fromFile($file3, 'public', 'files'),
    ]);

    $archive = $attachments->archive('documents.zip', 'public', 'archives');

    expect($archive)->toBeInstanceOf(Attachment::class)
        ->and($archive->disk())->toBe('public')
        ->and($archive->extname())->toBe('zip')
        ->and($archive->mimeType())->toBe('application/zip')
        ->and(Storage::disk('public')->exists($archive->path()))->toBeTrue()
        ->and($archive->size())->toBeGreaterThan(0);
});

it('throws exception when creating archive from empty collection', function () {
    $attachments = new Attachments([]);

    $attachments->archive('empty.zip');
})->throws(\NiftyCo\Attachments\Exceptions\StorageException::class, 'Cannot create archive from empty collection');

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

it('can filter attachments by type - documents', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->create('doc.pdf'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->image('photo.jpg'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->create('sheet.xlsx'), 'public', 'files'),
    ]);

    $documents = $attachments->ofType('document');

    expect($documents)->toHaveCount(2);
});

it('returns empty collection when filtering by unknown type', function () {
    $attachments = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->image('photo.jpg'), 'public', 'files'),
    ]);

    $filtered = $attachments->ofType('unknown');

    expect($filtered)->toBeEmpty();
});

it('preserves metadata when moving attachments', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos')
        ->withMetadata(['author' => 'John', 'tags' => ['nature']]);

    $attachments = new Attachments([$attachment]);
    $moved = $attachments->move('s3', 'archived');

    expect($moved->first()->getMeta('author'))->toBe('John')
        ->and($moved->first()->getMeta('tags'))->toBe(['nature']);
});

it('preserves metadata when copying attachments', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos')
        ->withMetadata(['author' => 'Jane', 'category' => 'landscape']);

    $attachments = new Attachments([$attachment]);
    $copied = $attachments->copy('backup', 'backups');

    expect($copied->first()->getMeta('author'))->toBe('Jane')
        ->and($copied->first()->getMeta('category'))->toBe('landscape');
});
