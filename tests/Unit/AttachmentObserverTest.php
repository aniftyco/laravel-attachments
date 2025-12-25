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

it('deletes single attachment when model is deleted', function () {
    config(['attachments.auto_cleanup' => true]);

    $model = new class extends Model
    {
        use HasAttachmentCleanup;

        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };

    $file = UploadedFile::fake()->image('avatar.jpg');
    $attachment = Attachment::fromFile($file, 'public');

    $model->avatar = $attachment;
    $model->save();

    // Verify file exists
    expect(Storage::disk('public')->exists($attachment->path()))->toBeTrue();

    // Delete model
    $model->delete();

    // Verify file was deleted
    expect(Storage::disk('public')->exists($attachment->path()))->toBeFalse();
});

it('deletes multiple attachments when model is deleted', function () {
    config(['attachments.auto_cleanup' => true]);

    $model = new class extends Model
    {
        use HasAttachmentCleanup;

        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'images' => AsAttachments::class,
            ];
        }
    };

    $file1 = UploadedFile::fake()->image('image1.jpg');
    $file2 = UploadedFile::fake()->image('image2.jpg');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public'),
        Attachment::fromFile($file2, 'public'),
    ]);

    $model->images = $attachments;
    $model->save();

    // Verify files exist
    foreach ($attachments as $attachment) {
        expect(Storage::disk('public')->exists($attachment->path()))->toBeTrue();
    }

    // Delete model
    $model->delete();

    // Verify files were deleted
    foreach ($attachments as $attachment) {
        expect(Storage::disk('public')->exists($attachment->path()))->toBeFalse();
    }
});

it('does not delete attachments when auto_cleanup is disabled', function () {
    config(['attachments.auto_cleanup' => false]);
    config(['attachments.delete_on_replace' => false]);

    $model = new class extends Model
    {
        use HasAttachmentCleanup;

        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };

    $file = UploadedFile::fake()->image('avatar.jpg');
    $attachment = Attachment::fromFile($file, 'public');

    $model->avatar = $attachment;
    $model->save();

    // Verify file exists
    expect(Storage::disk('public')->exists($attachment->path()))->toBeTrue();

    // Delete model
    $model->delete();

    // Verify file still exists (not deleted)
    expect(Storage::disk('public')->exists($attachment->path()))->toBeTrue();
});

it('handles null attachments gracefully', function () {
    config(['attachments.auto_cleanup' => true]);

    $model = new class extends Model
    {
        use HasAttachmentCleanup;

        protected $table = 'test_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };

    $model->avatar = null;
    $model->save();

    // Should not throw exception
    expect(fn () => $model->delete())->not->toThrow(\Exception::class);
});
