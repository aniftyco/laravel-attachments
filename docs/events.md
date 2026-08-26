# Events

Laravel Attachments dispatches events as attachments change on your models, so you can hook in to process files, scan them, log activity, or back them up.

Events are always dispatched. There is no toggle to turn them on or off; if you do not register a listener, nothing happens. They fire from the model observer after the row is saved or deleted, so the model's key is always available on the event.

## Available Events

### AttachmentCreated

Dispatched when an attachment is set on an attribute that had none:

```php
namespace NiftyCo\Attachments\Events;

use NiftyCo\Attachments\Attachment;

class AttachmentCreated
{
    public function __construct(
        public Attachment $attachment,
        public ?string $modelClass = null,
        public ?string $modelId = null,
        public ?string $attribute = null
    ) {}
}
```

### AttachmentUpdated

Dispatched when an attachment is replaced with a different file. It also carries the old attachment:

```php
namespace NiftyCo\Attachments\Events;

use NiftyCo\Attachments\Attachment;

class AttachmentUpdated
{
    public function __construct(
        public Attachment $attachment,
        public ?Attachment $oldAttachment = null,
        public ?string $modelClass = null,
        public ?string $modelId = null,
        public ?string $attribute = null
    ) {}
}
```

### AttachmentDeleted

Dispatched when an attachment is cleared or its model is deleted:

```php
namespace NiftyCo\Attachments\Events;

use NiftyCo\Attachments\Attachment;

class AttachmentDeleted
{
    public function __construct(
        public Attachment $attachment,
        public ?string $modelClass = null,
        public ?string $modelId = null,
        public ?string $attribute = null
    ) {}
}
```

Every event carries the source: `modelClass` and `modelId` identify the model row, and `attribute` is the cast attribute the attachment lives on.

## When Events Fire

On save, the observer diffs each attachment attribute's original value against its new one:

| Change on save                                   | Event dispatched            |
| ------------------------------------------------ | --------------------------- |
| Attachment set where there was none              | `AttachmentCreated`         |
| Attachment replaced with a different file        | `AttachmentUpdated` (old attachment attached) |
| Attachment cleared to `null`                     | `AttachmentDeleted`         |
| Collection: item added                           | `AttachmentCreated` per new item |
| Collection: item removed                         | `AttachmentDeleted` per removed item |
| Collection: item unchanged                       | Nothing                     |

On delete, the observer dispatches `AttachmentDeleted` for each attachment. Soft deletes are skipped: a soft-deleted model keeps its files and fires no event. Only a force delete (or a hard delete on a model without `SoftDeletes`) dispatches the deletion events. See [Automatic Cleanup](cleanup.md).

## Listening to Events

Laravel discovers listeners automatically by the event type they type-hint. Generate one:

```bash
php artisan make:listener ProcessUploadedImage
```

Type-hint the event in `handle()`:

```php
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use NiftyCo\Attachments\Events\AttachmentCreated;

class ProcessUploadedImage implements ShouldQueue
{
    public function handle(AttachmentCreated $event): void
    {
        $attachment = $event->attachment;

        if (! $attachment->isImage()) {
            return;
        }

        $this->createThumbnail($attachment);
    }

    private function createThumbnail($attachment): void
    {
        // Create thumbnail logic
    }
}
```

## Common Use Cases

### Image Processing

Process images after they are stored:

```php
namespace App\Listeners;

use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Events\AttachmentCreated;

class ProcessUploadedImage
{
    public function handle(AttachmentCreated $event): void
    {
        $attachment = $event->attachment;

        if (! $attachment->isImage()) {
            return;
        }

        $disk = Storage::disk($attachment->disk());
        $thumbnailPath = 'thumbnails/'.$attachment->path();

        // Read the source, resize with your image library, then write the thumbnail
        $disk->put($thumbnailPath, $this->resize($disk->get($attachment->path())));
    }
}
```

### Virus Scanning

Scan stored files and remove anything infected:

```php
namespace App\Listeners;

use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Events\AttachmentCreated;
use App\Services\VirusScanner;

class ScanUploadedFile
{
    public function __construct(
        private VirusScanner $scanner
    ) {}

    public function handle(AttachmentCreated $event): void
    {
        $attachment = $event->attachment;

        $result = $this->scanner->scan(
            Storage::disk($attachment->disk())->path($attachment->path())
        );

        if ($result->isInfected()) {
            $attachment->delete();

            logger()->warning('Infected file detected', [
                'file' => $attachment->path(),
                'virus' => $result->virusName(),
            ]);

            throw new \Exception('File is infected with malware');
        }
    }
}
```

### Logging

Log attachment activity, using the model identity the event carries:

```php
namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use NiftyCo\Attachments\Events\AttachmentCreated;
use NiftyCo\Attachments\Events\AttachmentDeleted;

class LogAttachmentOperations
{
    public function handleCreated(AttachmentCreated $event): void
    {
        Log::info('Attachment created', [
            'path' => $event->attachment->path(),
            'size' => $event->attachment->size(),
            'disk' => $event->attachment->disk(),
            'model' => $event->modelClass,
            'model_id' => $event->modelId,
            'attribute' => $event->attribute,
        ]);
    }

    public function handleDeleted(AttachmentDeleted $event): void
    {
        Log::info('Attachment deleted', [
            'path' => $event->attachment->path(),
            'model' => $event->modelClass,
            'model_id' => $event->modelId,
        ]);
    }
}
```

Register both handlers where you subscribe to events, or split them into dedicated listener classes and let discovery wire them up.

### Cleaning Up After a Replacement

`AttachmentUpdated` carries the file that was replaced, which is handy when you keep derived files alongside the original:

```php
namespace App\Listeners;

use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Events\AttachmentUpdated;

class RemoveStaleThumbnail
{
    public function handle(AttachmentUpdated $event): void
    {
        if ($event->oldAttachment === null) {
            return;
        }

        Storage::disk($event->oldAttachment->disk())
            ->delete('thumbnails/'.$event->oldAttachment->path());
    }
}
```

### Backup to Cloud

Copy each new file to a secondary location:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;

class BackupAttachment
{
    public function handle(AttachmentCreated $event): void
    {
        $event->attachment->duplicate('s3-backup', 'backups/'.now()->format('Y/m'));
    }
}
```

## Faking Events in Tests

To assert an event fired, or to silence listeners for a specific test, fake it:

```php
use Illuminate\Support\Facades\Event;
use NiftyCo\Attachments\Events\AttachmentCreated;

Event::fake([AttachmentCreated::class]);

$user->avatar = Attachment::fromFile($file, folder: 'avatars');
$user->save();

Event::assertDispatched(AttachmentCreated::class);
```

## Queued Listeners

For slow work, implement `ShouldQueue` so the listener runs on a queue:

```php
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use NiftyCo\Attachments\Events\AttachmentCreated;

class ProcessUploadedImage implements ShouldQueue
{
    public $queue = 'image-processing';

    public function handle(AttachmentCreated $event): void
    {
        // Time-consuming image processing
    }
}
```

## Best Practices

### Handle Failures Gracefully

```php
public function handle(AttachmentCreated $event): void
{
    try {
        $this->processImage($event->attachment);
    } catch (\Exception $e) {
        Log::error('Image processing failed', [
            'attachment' => $event->attachment->path(),
            'error' => $e->getMessage(),
        ]);
    }
}
```

### Keep Listeners Focused

Give each listener one job. A `CreateThumbnail` listener and an `OptimizeImage` listener are easier to test and reason about than a single `ProcessUploadedFile` that does both.

## Next Steps

- Learn about [Testing](testing.md)
- Explore [API Resources](api-resources.md)
- Configure [Automatic Cleanup](cleanup.md)
