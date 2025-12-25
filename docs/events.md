# Events

Laravel Attachments dispatches events during the attachment lifecycle, allowing you to hook into file operations and perform custom actions.

## Available Events

### AttachmentCreated

Dispatched when a new attachment is created:

```php
use NiftyCo\Attachments\Events\AttachmentCreated;

class AttachmentCreated
{
    public function __construct(
        public Attachment $attachment
    ) {}
}
```

### AttachmentUpdated

Dispatched when an attachment is updated:

```php
use NiftyCo\Attachments\Events\AttachmentUpdated;

class AttachmentUpdated
{
    public function __construct(
        public Attachment $attachment,
        public Attachment $oldAttachment
    ) {}
}
```

### AttachmentDeleted

Dispatched when an attachment is deleted:

```php
use NiftyCo\Attachments\Events\AttachmentDeleted;

class AttachmentDeleted
{
    public function __construct(
        public Attachment $attachment
    ) {}
}
```

## Listening to Events

### Creating a Listener

Generate a listener:

```bash
php artisan make:listener ProcessUploadedImage
```

Implement the listener:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessUploadedImage implements ShouldQueue
{
    public function handle(AttachmentCreated $event): void
    {
        $attachment = $event->attachment;

        // Only process images
        if (!$attachment->isImage()) {
            return;
        }

        // Process the image
        $this->createThumbnail($attachment);
        $this->optimizeImage($attachment);
    }

    private function createThumbnail($attachment): void
    {
        // Create thumbnail logic
    }

    private function optimizeImage($attachment): void
    {
        // Optimize image logic
    }
}
```

### Registering the Listener

Register in `EventServiceProvider`:

```php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use NiftyCo\Attachments\Events\AttachmentCreated;
use App\Listeners\ProcessUploadedImage;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AttachmentCreated::class => [
            ProcessUploadedImage::class,
        ],
    ];
}
```

## Common Use Cases

### Image Processing

Process images after upload:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProcessUploadedImage
{
    public function handle(AttachmentCreated $event): void
    {
        $attachment = $event->attachment;

        if (!$attachment->isImage()) {
            return;
        }

        $disk = Storage::disk($attachment->disk());
        $path = $attachment->path();

        // Create thumbnail
        $image = Image::make($disk->get($path));
        $thumbnail = $image->fit(200, 200);

        $thumbnailPath = str_replace(
            $attachment->name(),
            'thumbnails/' . $attachment->name(),
            $path
        );

        $disk->put($thumbnailPath, $thumbnail->encode());

        // Store thumbnail path in metadata
        $attachment->setMetadata('thumbnail', $thumbnailPath);
    }
}
```

### Virus Scanning

Scan uploaded files for viruses:

```php
namespace App\Listeners;

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
            Storage::disk($attachment->disk)->path($attachment->name)
        );

        if ($result->isInfected()) {
            // Delete infected file
            $attachment->delete();

            // Log the incident
            logger()->warning('Infected file detected', [
                'file' => $attachment->name,
                'virus' => $result->virusName(),
            ]);

            throw new \Exception('File is infected with malware');
        }

        // Mark as scanned
        $attachment->setMetadata('scanned', true);
        $attachment->setMetadata('scanned_at', now()->toIso8601String());
    }
}
```

### Logging

Log all attachment operations:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;
use NiftyCo\Attachments\Events\AttachmentDeleted;
use Illuminate\Support\Facades\Log;

class LogAttachmentOperations
{
    public function handleCreated(AttachmentCreated $event): void
    {
        Log::info('Attachment created', [
            'name' => $event->attachment->name,
            'size' => $event->attachment->size,
            'disk' => $event->attachment->disk,
            'user' => auth()->id(),
        ]);
    }

    public function handleDeleted(AttachmentDeleted $event): void
    {
        Log::info('Attachment deleted', [
            'name' => $event->attachment->name,
            'user' => auth()->id(),
        ]);
    }
}
```

Register multiple event handlers:

```php
protected $listen = [
    AttachmentCreated::class => [
        LogAttachmentOperations::class . '@handleCreated',
    ],
    AttachmentDeleted::class => [
        LogAttachmentOperations::class . '@handleDeleted',
    ],
];
```

### Notifications

Notify users when files are uploaded:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;
use App\Notifications\FileUploadedNotification;

class NotifyFileUpload
{
    public function handle(AttachmentCreated $event): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        $user->notify(new FileUploadedNotification($event->attachment));
    }
}
```

### Backup to Cloud

Automatically backup files to a secondary location:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;

class BackupAttachment
{
    public function handle(AttachmentCreated $event): void
    {
        $attachment = $event->attachment;

        // Copy to backup disk
        $attachment->copy('s3-backup', 'backups/' . now()->format('Y/m'));
    }
}
```

## Disabling Events

### Globally

Disable events in configuration:

```php
// config/attachments.php
return [
    'events' => [
        'enabled' => false,
    ],
];
```

Or via environment variable:

```env
ATTACHMENTS_EVENTS_ENABLED=false
```

### Temporarily

Disable events for specific operations:

```php
use Illuminate\Support\Facades\Event;

Event::fake([
    AttachmentCreated::class,
]);

// Upload without triggering events
$user->avatar = Attachment::fromFile($file, folder: 'avatars');
$user->save();
```

## Queued Listeners

For time-consuming operations, use queued listeners:

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

### 1. Use Queued Listeners for Heavy Operations

```php
class ProcessUploadedImage implements ShouldQueue
{
    // Heavy processing in background
}
```

### 2. Handle Failures Gracefully

```php
public function handle(AttachmentCreated $event): void
{
    try {
        $this->processImage($event->attachment);
    } catch (\Exception $e) {
        Log::error('Image processing failed', [
            'attachment' => $event->attachment->name,
            'error' => $e->getMessage(),
        ]);
    }
}
```

### 3. Keep Listeners Focused

```php
// Good: Single responsibility
class CreateThumbnail { }
class OptimizeImage { }
class ScanForViruses { }

// Bad: Multiple responsibilities
class ProcessUploadedFile { }
```

## Next Steps

- Learn about [Testing](testing.md)
- Explore [API Resources](api-resources.md)
- Configure [Metadata](metadata.md)
