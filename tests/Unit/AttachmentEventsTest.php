<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;
use NiftyCo\Attachments\Concerns\HasAttachments;
use NiftyCo\Attachments\Events\AttachmentCreated;
use NiftyCo\Attachments\Events\AttachmentDeleted;
use NiftyCo\Attachments\Events\AttachmentUpdated;

beforeEach(function () {
    Storage::fake('public');
    config(['attachments.delete_on_replace' => true]);

    Schema::create('event_models', function (Blueprint $table) {
        $table->id();
        $table->text('avatar')->nullable();
        $table->text('images')->nullable();
        $table->timestamps();
    });

    Schema::create('soft_event_models', function (Blueprint $table) {
        $table->id();
        $table->text('avatar')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('event_models');
    Schema::dropIfExists('soft_event_models');
});

function eventModel(): Model
{
    return new class extends Model
    {
        use HasAttachments;

        protected $table = 'event_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
                'images' => AsAttachments::class,
            ];
        }
    };
}

function softEventModel(): Model
{
    return new class extends Model
    {
        use HasAttachments;
        use SoftDeletes;

        protected $table = 'soft_event_models';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'avatar' => AsAttachment::class,
            ];
        }
    };
}

// Fake only our attachment events so the Eloquent "saved"/"deleting" model
// events still fire and drive the observer.
function fakeAttachmentEvents(): void
{
    Event::fake([AttachmentCreated::class, AttachmentUpdated::class, AttachmentDeleted::class]);
}

it('dispatches AttachmentCreated with the saved model id when an attachment is created', function () {
    fakeAttachmentEvents();

    $model = eventModel();
    $model->avatar = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->save();

    Event::assertDispatched(AttachmentCreated::class, function ($event) use ($model) {
        return $event->attribute === 'avatar'
            && $event->modelClass === get_class($model)
            && $event->modelId === (string) $model->getKey()
            && $event->modelId !== null;
    });
    Event::assertNotDispatched(AttachmentUpdated::class);
    Event::assertNotDispatched(AttachmentDeleted::class);
});

it('dispatches AttachmentUpdated when a single attachment is replaced', function () {
    $model = eventModel();
    $old = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->avatar = $old;
    $model->save();

    fakeAttachmentEvents();

    $new = Attachment::fromFile(UploadedFile::fake()->image('b.jpg'), 'public');
    $model->avatar = $new;
    $model->save();

    Event::assertDispatched(AttachmentUpdated::class, function ($event) use ($model, $old, $new) {
        return $event->attachment->path() === $new->path()
            && $event->oldAttachment?->path() === $old->path()
            && $event->modelId === (string) $model->getKey();
    });
    Event::assertNotDispatched(AttachmentCreated::class);
});

it('dispatches AttachmentDeleted when a single attachment is cleared on save', function () {
    $model = eventModel();
    $old = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->avatar = $old;
    $model->save();

    fakeAttachmentEvents();

    $model->avatar = null;
    $model->save();

    Event::assertDispatched(AttachmentDeleted::class, function ($event) use ($model, $old) {
        return $event->attachment->path() === $old->path()
            && $event->attribute === 'avatar'
            && $event->modelId === (string) $model->getKey();
    });
    Event::assertNotDispatched(AttachmentCreated::class);
    Event::assertNotDispatched(AttachmentUpdated::class);
});

it('does not dispatch an update when a single attachment is unchanged', function () {
    $model = eventModel();
    $model->avatar = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->save();

    fakeAttachmentEvents();

    $model->touch();

    Event::assertNotDispatched(AttachmentCreated::class);
    Event::assertNotDispatched(AttachmentUpdated::class);
});

it('dispatches AttachmentCreated only for attachments added to a collection', function () {
    fakeAttachmentEvents();

    $model = eventModel();
    $keep = Attachment::fromFile(UploadedFile::fake()->image('keep.jpg'), 'public');
    $drop = Attachment::fromFile(UploadedFile::fake()->image('drop.jpg'), 'public');
    $model->images = new Attachments([$keep, $drop]);
    $model->save();

    $add = Attachment::fromFile(UploadedFile::fake()->image('add.jpg'), 'public');
    $model->images = new Attachments([$keep, $add]);
    $model->save();

    // keep + drop on the first save, add on the second; keep is retained (no event).
    Event::assertDispatchedTimes(AttachmentCreated::class, 3);
    Event::assertDispatched(AttachmentCreated::class, function ($event) use ($add, $model) {
        return $event->attachment->path() === $add->path()
            && $event->modelId === (string) $model->getKey();
    });

    // drop was removed on the second save → one Deleted.
    Event::assertDispatchedTimes(AttachmentDeleted::class, 1);
    Event::assertDispatched(AttachmentDeleted::class, function ($event) use ($drop, $model) {
        return $event->attachment->path() === $drop->path()
            && $event->attribute === 'images'
            && $event->modelId === (string) $model->getKey();
    });
});

it('dispatches AttachmentDeleted for each attachment when the model is deleted', function () {
    $model = eventModel();
    $model->avatar = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->save();
    $key = (string) $model->getKey();

    fakeAttachmentEvents();

    $model->delete();

    Event::assertDispatched(AttachmentDeleted::class, function ($event) use ($model, $key) {
        return $event->attribute === 'avatar'
            && $event->modelClass === get_class($model)
            && $event->modelId === $key;
    });
});

it('still purges files and dispatches Deleted on a hard delete of a non-softdeletes model', function () {
    $model = eventModel();
    $attachment = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->avatar = $attachment;
    $model->save();
    $path = $attachment->path();

    fakeAttachmentEvents();

    $model->delete();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
    Event::assertDispatched(AttachmentDeleted::class, fn ($event) => $event->attachment->path() === $path
        && $event->modelId === (string) $model->getKey());
});

it('does not purge files or dispatch Deleted when a softdeletes model is soft deleted', function () {
    $model = softEventModel();
    $attachment = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->avatar = $attachment;
    $model->save();
    $path = $attachment->path();

    fakeAttachmentEvents();

    $model->delete();

    expect($model->trashed())->toBeTrue()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
    Event::assertNotDispatched(AttachmentDeleted::class);
});

it('purges files and dispatches Deleted when a softdeletes model is force deleted', function () {
    $model = softEventModel();
    $attachment = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'), 'public');
    $model->avatar = $attachment;
    $model->save();
    $path = $attachment->path();
    $key = (string) $model->getKey();

    fakeAttachmentEvents();

    $model->forceDelete();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
    Event::assertDispatched(AttachmentDeleted::class, fn ($event) => $event->attachment->path() === $path
        && $event->attribute === 'avatar'
        && $event->modelId === $key);
});
