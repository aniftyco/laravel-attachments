<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;
use NiftyCo\Attachments\Concerns\HasAttachmentCleanup;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');

    // Create test table
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('cover')->nullable();
        $table->text('files')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('documents');
});

it('can save and retrieve single attachment on model', function () {
    $model = createDocumentModel();
    $file = UploadedFile::fake()->image('cover.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'covers');
    $model->cover = $attachment;
    $model->save();

    $retrieved = $model->fresh();

    expect($retrieved->cover)->toBeInstanceOf(Attachment::class)
        ->and($retrieved->cover->name())->toBe($attachment->name())
        ->and($retrieved->cover->disk())->toBe('public')
        ->and($retrieved->cover->extname())->toBe('jpg');
});

it('can save and retrieve multiple attachments on model', function () {
    $model = createDocumentModel();
    $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
    $file2 = UploadedFile::fake()->create('doc2.pdf', 200);

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public', 'files'),
        Attachment::fromFile($file2, 'public', 'files'),
    ]);

    $model->files = $attachments;
    $model->save();

    $retrieved = $model->fresh();

    expect($retrieved->files)->toBeInstanceOf(Attachments::class)
        ->and($retrieved->files)->toHaveCount(2)
        ->and($retrieved->files->first()->name())->toContain('.pdf')
        ->and($retrieved->files->last()->name())->toContain('.pdf');
});

it('replaces old attachment when updating', function () {
    $model = createDocumentModel();
    $file1 = UploadedFile::fake()->image('old.jpg');
    $file2 = UploadedFile::fake()->image('new.jpg');

    // Save first attachment
    $oldAttachment = Attachment::fromFile($file1, 'public', 'covers');
    $model->cover = $oldAttachment;
    $model->save();

    $oldPath = $oldAttachment->path();
    expect(Storage::disk('public')->exists($oldPath))->toBeTrue();

    // Replace with new attachment
    $newAttachment = Attachment::fromFile($file2, 'public', 'covers');
    $model->cover = $newAttachment;
    $model->save();

    // Old file should be deleted
    expect(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($newAttachment->path()))->toBeTrue();
});

it('deletes attachment when model is deleted with auto cleanup', function () {
    config(['attachments.auto_cleanup' => true]);

    $model = createDocumentModel();
    $file = UploadedFile::fake()->image('cover.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'covers');
    $model->cover = $attachment;
    $model->save();

    $path = $attachment->path();
    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $model->delete();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

it('can set attachment to null', function () {
    $model = createDocumentModel();
    $file = UploadedFile::fake()->image('cover.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'covers');
    $model->cover = $attachment;
    $model->save();

    $path = $attachment->path();

    $model->cover = null;
    $model->save();

    $retrieved = $model->fresh();

    expect($retrieved->cover)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('handles multiple attachments replacement', function () {
    $model = createDocumentModel();

    // First set of files
    $files1 = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->create('doc1.pdf'), 'public', 'files'),
        Attachment::fromFile(UploadedFile::fake()->create('doc2.pdf'), 'public', 'files'),
    ]);

    $model->files = $files1;
    $model->save();

    $oldPaths = $files1->map(fn ($a) => $a->path())->toArray();

    // Replace with new files
    $files2 = new Attachments([
        Attachment::fromFile(UploadedFile::fake()->create('doc3.pdf'), 'public', 'files'),
    ]);

    $model->files = $files2;
    $model->save();

    // Old files should be deleted
    foreach ($oldPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }

    expect(Storage::disk('public')->exists($files2->first()->path()))->toBeTrue();
});

function createDocumentModel(): Model
{
    $model = new class extends Model
    {
        use HasAttachmentCleanup;

        protected $table = 'documents';
        protected $guarded = [];

        protected $casts = [
            'cover' => AsAttachment::class,
            'files' => AsAttachments::class,
        ];
    };

    $model->title = 'Test Document';

    return $model;
}
