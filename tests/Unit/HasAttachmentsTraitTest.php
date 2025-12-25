<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;
use NiftyCo\Attachments\Concerns\HasAttachments;

class TestModelWithAttachments extends Model
{
    use HasAttachments;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
            'photos' => AsAttachments::class,
        ];
    }
}

beforeEach(function () {
    Storage::fake('public');
});

it('can attach a file to an attribute', function () {
    $model = new TestModelWithAttachments;
    $file = UploadedFile::fake()->image('avatar.jpg');

    $model->attachFile('avatar', $file, 'public', 'avatars');

    expect($model->avatar)->not->toBeNull()
        ->and($model->avatar->extname())->toBe('jpg')
        ->and($model->avatar->disk())->toBe('public');
});

it('can attach multiple files to an attribute', function () {
    $model = new TestModelWithAttachments;
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
        UploadedFile::fake()->image('photo3.jpg'),
    ];

    $model->attachFiles('photos', $files, 'public', 'photos');

    expect($model->photos)->toHaveCount(3);
});

it('can add attachment to existing collection', function () {
    $model = new TestModelWithAttachments;
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
    ];

    $model->attachFiles('photos', $files, 'public', 'photos');

    expect($model->photos)->toHaveCount(2);

    $model->addAttachment('photos', UploadedFile::fake()->image('photo3.jpg'), 'public', 'photos');

    expect($model->photos)->toHaveCount(3);
});

it('can remove attachment by name', function () {
    $model = new TestModelWithAttachments;
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
        UploadedFile::fake()->image('photo3.jpg'),
    ];

    $model->attachFiles('photos', $files, 'public', 'photos');

    expect($model->photos)->toHaveCount(3);

    // Get the actual stored name of the second photo
    $secondPhotoName = $model->photos->get(1)->name();
    $model->removeAttachment('photos', $secondPhotoName);

    expect($model->photos)->toHaveCount(2)
        ->and($model->photos->pluck('name')->toArray())->not->toContain($secondPhotoName);
});

it('can clear all attachments without deleting files', function () {
    $model = new TestModelWithAttachments;
    $file = UploadedFile::fake()->image('avatar.jpg');

    $model->attachFile('avatar', $file, 'public', 'avatars');
    $path = $model->avatar->path();

    expect($model->avatar)->not->toBeNull();

    $model->clearAttachments('avatar', deleteFiles: false);

    expect($model->avatar)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('can clear all attachments and delete files', function () {
    $model = new TestModelWithAttachments;
    $file = UploadedFile::fake()->image('avatar.jpg');

    $model->attachFile('avatar', $file, 'public', 'avatars');
    $path = $model->avatar->path();

    expect($model->avatar)->not->toBeNull();

    $model->clearAttachments('avatar', deleteFiles: true);

    expect($model->avatar)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('can get all attachment attributes', function () {
    $model = new TestModelWithAttachments;

    $attributes = $model->getAttachmentAttributes();

    expect($attributes)->toContain('avatar')
        ->and($attributes)->toContain('photos');
});

it('can check if model has attachments', function () {
    $model = new TestModelWithAttachments;

    expect($model->hasAttachments())->toBeFalse();

    $model->attachFile('avatar', UploadedFile::fake()->image('avatar.jpg'), 'public', 'avatars');

    expect($model->hasAttachments())->toBeTrue();
});

it('can calculate total attachments size', function () {
    $model = new TestModelWithAttachments;

    $model->attachFile('avatar', UploadedFile::fake()->create('avatar.jpg', 100), 'public', 'avatars');
    $model->attachFiles('photos', [
        UploadedFile::fake()->create('photo1.jpg', 200),
        UploadedFile::fake()->create('photo2.jpg', 300),
    ], 'public', 'photos');

    $totalSize = $model->totalAttachmentsSize();

    expect($totalSize)->toBeGreaterThan(500000); // At least 500KB (100+200+300)
});

it('returns zero size when no attachments', function () {
    $model = new TestModelWithAttachments;

    expect($model->totalAttachmentsSize())->toBe(0);
});

it('can add attachment to null collection', function () {
    $model = new TestModelWithAttachments;

    expect($model->photos)->toBeEmpty();

    $model->addAttachment('photos', UploadedFile::fake()->image('photo1.jpg'), 'public', 'photos');

    expect($model->photos)->toHaveCount(1);
});
