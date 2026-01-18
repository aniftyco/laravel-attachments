<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use Workbench\App\Models\Document;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    // Run the workbench migration
    Schema::create('documents', function ($table) {
        $table->id();
        $table->string('title');
        $table->attachment('file');
        $table->attachments('attachments');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('documents');
});

it('can create document with single attachment using workbench model', function () {
    $file = UploadedFile::fake()->image('document.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'documents');
    $path = $attachment->path();

    // Verify file exists before DB operation
    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $document = Document::create([
        'title' => 'Test Document',
        'file' => $attachment,
    ]);

    expect($document->file)->toBeInstanceOf(Attachment::class)
        ->and($document->file->disk())->toBe('public')
        ->and($document->file->path())->toBe($path);
});

it('can create document with multiple attachments using workbench model', function () {
    $file1 = UploadedFile::fake()->create('doc1.pdf');
    $file2 = UploadedFile::fake()->create('doc2.pdf');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'documents'),
        Attachment::fromFile($file2, 'public', 'documents'),
    ]);

    $document = Document::create([
        'title' => 'Multi-file Document',
        'attachments' => $attachments,
    ]);

    expect($document->attachments)->toBeInstanceOf(Attachments::class)
        ->and($document->attachments)->toHaveCount(2);
});

it('uses config default disk when not specified', function () {
    Config::set('attachments.disk', 'local');
    Config::set('attachments.folder', 'uploads');
    Storage::fake('local');

    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file);

    expect($attachment->disk())->toBe('local')
        ->and($attachment->folder())->toBe('uploads')
        ->and($attachment->exists())->toBeTrue();
});

it('uses config default folder when not specified', function () {
    Config::set('attachments.folder', 'custom-folder');

    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public');

    expect($attachment->folder())->toBe('custom-folder');
});

it('respects disk visibility configuration for uploads', function () {
    Config::set('filesystems.disks.public.visibility', 'public');
    Config::set('filesystems.disks.local.visibility', 'private');
    Storage::fake('public');
    Storage::fake('local');

    $publicFile = UploadedFile::fake()->image('public.jpg');
    $privateFile = UploadedFile::fake()->image('private.jpg');

    $publicAttachment = Attachment::fromFile($publicFile, 'public', 'files');
    $privateAttachment = Attachment::fromFile($privateFile, 'local', 'files');

    expect(Storage::disk('public')->getVisibility($publicAttachment->path()))->toBe('public')
        ->and(Storage::disk('local')->getVisibility($privateAttachment->path()))->toBe('private');
});

it('respects disk visibility configuration for move operations', function () {
    Config::set('filesystems.disks.public.visibility', 'public');
    Config::set('filesystems.disks.local.visibility', 'private');
    Storage::fake('public');
    Storage::fake('local');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'local', 'files');

    // Move from private to public disk
    $moved = $attachment->move('public', 'moved');

    expect(Storage::disk('public')->getVisibility($moved->path()))->toBe('public');
});

it('respects disk visibility configuration for duplicate operations', function () {
    Config::set('filesystems.disks.public.visibility', 'public');
    Config::set('filesystems.disks.local.visibility', 'private');
    Storage::fake('public');
    Storage::fake('local');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'local', 'files');

    // Duplicate from private to public disk
    $duplicate = $attachment->duplicate('public', 'duplicates');

    expect(Storage::disk('public')->getVisibility($duplicate->path()))->toBe('public')
        ->and($attachment->exists())->toBeTrue(); // Original still exists
});

it('cleans up attachments when document is deleted', function () {
    Config::set('attachments.auto_cleanup', true);

    $file = UploadedFile::fake()->image('cleanup-test.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'documents');

    $document = Document::create([
        'title' => 'Cleanup Test',
        'file' => $attachment,
    ]);

    $path = $attachment->path();
    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $document->delete();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

it('replaces old attachment when updating document', function () {
    Config::set('attachments.delete_on_replace', true);

    $oldFile = UploadedFile::fake()->image('old.jpg');
    $newFile = UploadedFile::fake()->image('new.jpg');

    $oldAttachment = Attachment::fromFile($oldFile, 'public', 'documents');
    $document = Document::create([
        'title' => 'Replace Test',
        'file' => $oldAttachment,
    ]);

    $oldPath = $oldAttachment->path();

    $newAttachment = Attachment::fromFile($newFile, 'public', 'documents');
    $document->file = $newAttachment;
    $document->save();

    expect(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($newAttachment->path()))->toBeTrue();
});

it('works with HasAttachments trait methods', function () {
    $file1 = UploadedFile::fake()->image('attach1.jpg');
    $file2 = UploadedFile::fake()->image('attach2.jpg');

    $document = Document::create([
        'title' => 'Trait Test',
    ]);

    // Use addAttachment method from HasAttachments trait
    $document->addAttachment('attachments', $file1, 'public', 'trait-test');
    $document->addAttachment('attachments', $file2, 'public', 'trait-test');
    $document->save();

    $fresh = $document->fresh();

    expect($fresh->attachments)->toHaveCount(2)
        ->and($fresh->hasAttachments())->toBeTrue()
        ->and($fresh->totalAttachmentsSize())->toBeGreaterThan(0);
});
