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

beforeEach(function () {
    Storage::fake('public');

    // Create test table
    Schema::create('test_models', function (Blueprint $table) {
        $table->id();
        $table->jsonb('avatar')->nullable();
        $table->jsonb('images')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_models');
});

it('deletes old attachment when replaced with new one', function () {
    config(['attachments.delete_on_replace' => true]);

    $model = new class extends Model
    {
        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };

    // Create and save first attachment
    $file1 = UploadedFile::fake()->image('avatar1.jpg');
    $attachment1 = Attachment::fromFile($file1, 'public');
    $oldPath = $attachment1->path();

    $model->avatar = $attachment1;
    $model->save();
    $model->refresh();

    // Verify first file exists
    expect(Storage::disk('public')->exists($oldPath))->toBeTrue();

    // Replace with second attachment
    $file2 = UploadedFile::fake()->image('avatar2.jpg');
    $attachment2 = Attachment::fromFile($file2, 'public');
    $model->avatar = $attachment2;
    $model->save();

    // Old file should be deleted
    expect(Storage::disk('public')->exists($oldPath))->toBeFalse();
    // New file should exist
    expect(Storage::disk('public')->exists($attachment2->path()))->toBeTrue();
});

it('deletes old attachments when replaced with new collection', function () {
    config(['attachments.delete_on_replace' => true]);

    $model = new class extends Model
    {
        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'images' => AsAttachments::class,
            ];
        }
    };

    // Create and save first set of attachments
    $file1 = UploadedFile::fake()->image('image1.jpg');
    $file2 = UploadedFile::fake()->image('image2.jpg');
    $attachments1 = new Attachments([
        Attachment::fromFile($file1, 'public'),
        Attachment::fromFile($file2, 'public'),
    ]);
    $oldPaths = $attachments1->map(fn ($a) => $a->path())->toArray();

    $model->images = $attachments1;
    $model->save();
    $model->refresh();

    // Verify first files exist
    foreach ($oldPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }

    // Replace with new set of attachments
    $file3 = UploadedFile::fake()->image('image3.jpg');
    $attachments2 = new Attachments([
        Attachment::fromFile($file3, 'public'),
    ]);
    $model->images = $attachments2;
    $model->save();

    // Old files should be deleted
    foreach ($oldPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }
    // New file should exist
    expect(Storage::disk('public')->exists($attachments2->first()->path()))->toBeTrue();
});

it('does not delete old attachment when delete_on_replace is disabled', function () {
    config(['attachments.delete_on_replace' => false]);

    $model = new class extends Model
    {
        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };

    // Create and save first attachment
    $file1 = UploadedFile::fake()->image('avatar1.jpg');
    $attachment1 = Attachment::fromFile($file1, 'public');
    $model->avatar = $attachment1;
    $model->save();

    $oldPath = $attachment1->path();

    // Replace with second attachment
    $file2 = UploadedFile::fake()->image('avatar2.jpg');
    $attachment2 = Attachment::fromFile($file2, 'public');
    $model->avatar = $attachment2;
    $model->save();

    // Old file should still exist
    expect(Storage::disk('public')->exists($oldPath))->toBeTrue();
    // New file should also exist
    expect(Storage::disk('public')->exists($attachment2->path()))->toBeTrue();
});

it('does not delete new attachment when replacing via request pattern', function () {
    config(['attachments.delete_on_replace' => true]);

    $model = new class extends Model
    {
        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };

    // Simulate: user already exists with an avatar
    $existingFile = UploadedFile::fake()->image('existing-avatar.jpg');
    $existingAttachment = Attachment::fromFile($existingFile, 'public');
    $model->avatar = $existingAttachment;
    $model->save();

    $existingPath = $existingAttachment->path();
    expect(Storage::disk('public')->exists($existingPath))->toBeTrue();

    // Now simulate the request pattern (no refresh - same model instance)
    $newFile = UploadedFile::fake()->image('new-avatar.jpg');
    $newAttachment = Attachment::fromFile($newFile, 'public');
    $newPath = $newAttachment->path();

    // File should exist immediately after fromFile
    expect(Storage::disk('public')->exists($newPath))->toBeTrue();

    // Now set it on the model (this triggers the cast's set method)
    $model->avatar = $newAttachment;

    // New file should STILL exist after assignment
    expect(Storage::disk('public')->exists($newPath))->toBeTrue();

    // Save the model
    $model->save();

    // After save: old should be deleted, new should exist
    expect(Storage::disk('public')->exists($existingPath))->toBeFalse();
    expect(Storage::disk('public')->exists($newPath))->toBeTrue();
});
