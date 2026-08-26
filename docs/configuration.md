# Configuration

Laravel Attachments comes with sensible defaults, but you can customize every aspect of its behavior.

## Publishing the Configuration

To customize the configuration, publish the config file:

```bash
php artisan vendor:publish --tag=attachments-config
```

This creates `config/attachments.php` in your application.

## Configuration Options

### Default Storage Disk

The default filesystem disk for storing attachments:

```php
'disk' => env('ATTACHMENTS_DISK'),
```

**Environment Variable:**
```env
ATTACHMENTS_DISK=s3
```

When left unset, the package falls back to the framework's default filesystem
disk (`config('filesystems.default')`), resolved at runtime — so there is no
hardcoded `public` default. You can use any disk defined in `config/filesystems.php`:
- `local` - Local storage (not publicly accessible)
- `public` - Public storage (accessible via URL)
- `s3` - Amazon S3
- Any custom disk you've configured

### Default Storage Folder

The default folder path where attachments are stored:

```php
'folder' => env('ATTACHMENTS_FOLDER', 'attachments'),
```

**Environment Variable:**
```env
ATTACHMENTS_FOLDER=attachments
```

This can be overridden per-attachment when uploading files.

### Auto Cleanup

Automatically delete files when the parent model is deleted:

```php
'auto_cleanup' => env('ATTACHMENTS_AUTO_CLEANUP', true),
```

**Environment Variable:**
```env
ATTACHMENTS_AUTO_CLEANUP=true
```

**Note:** Requires the `HasAttachmentCleanup` trait on your model.

### Delete on Replace

Automatically delete old files when replaced with new ones:

```php
'delete_on_replace' => env('ATTACHMENTS_DELETE_ON_REPLACE', true),
```

**Environment Variable:**
```env
ATTACHMENTS_DELETE_ON_REPLACE=true
```

When enabled, replacing an attachment automatically deletes the old file from storage.

### File Naming Strategy

Controls how stored files are named:

```php
'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'random'),
```

**Environment Variable:**
```env
ATTACHMENTS_NAMING_STRATEGY=random
```

Accepts one of the three built-in strategies, or the fully-qualified class name of a custom one:

- `random` (default) - A random 40-character name plus the original extension.
- `uuid` - A UUID name plus the original extension.
- `original` - The client filename, sanitized. On a collision it appends a short random suffix; when there is no client name (for example an attachment built from raw content), it falls back to the random strategy.

To name files your own way, point this at a class that implements `NiftyCo\Attachments\Naming\NamingStrategy`. See [Naming Strategies](#naming-strategies) below.

### Temporary URL Expiration

Default expiration time (in minutes) for temporary URLs:

```php
'temporary_url_expiration' => env('ATTACHMENTS_TEMPORARY_URL_EXPIRATION', 60),
```

**Environment Variable:**
```env
ATTACHMENTS_TEMPORARY_URL_EXPIRATION=60
```

This applies to private disks that support temporary URLs (like S3).

## Naming Strategies

The `naming_strategy` option decides the storage path for every file the package writes. Three strategies ship with the package:

| Strategy   | Result                                                                 |
| ---------- | ---------------------------------------------------------------------- |
| `random`   | A random 40-character name plus the original extension.                |
| `uuid`     | A UUID name plus the original extension.                               |
| `original` | The sanitized client filename, with a random suffix added on collision. |

### Writing a Custom Strategy

A strategy is any class implementing `NiftyCo\Attachments\Naming\NamingStrategy`. Its `__invoke()` receives an `AttachmentContext` and returns the full storage path (folder and filename) relative to the disk root:

```php
namespace App\Attachments;

use NiftyCo\Attachments\Naming\AttachmentContext;
use NiftyCo\Attachments\Naming\NamingStrategy;

class DatePrefixedStrategy implements NamingStrategy
{
    public function __invoke(AttachmentContext $context): string
    {
        $name = now()->format('Y/m/d').'/'.\Illuminate\Support\Str::random(20);

        if ($context->extension) {
            $name .= '.'.$context->extension;
        }

        return trim($context->folder, '/') !== ''
            ? trim($context->folder, '/').'/'.$name
            : $name;
    }
}
```

The `AttachmentContext` exposes everything known about the incoming file:

| Property       | Type            | Description                          |
| -------------- | --------------- | ------------------------------------ |
| `originalName` | `?string`       | Client filename, if any              |
| `extension`    | `?string`       | File extension, if any               |
| `mimeType`     | `?string`       | MIME type, if known                  |
| `size`         | `?int`          | Size in bytes, if known              |
| `folder`       | `string`        | Target folder                        |
| `disk`         | `string`        | Target disk                          |

Point the config at your class to use it. It is resolved from the container, so you can type-hint dependencies in its constructor:

```php
'naming_strategy' => \App\Attachments\DatePrefixedStrategy::class,
```

## Per-Attachment Configuration

You can override the disk and folder when creating attachments:

```php
use NiftyCo\Attachments\Attachment;

$attachment = Attachment::fromFile(
    $file,
    disk: 's3',             // Override disk
    folder: 'user-uploads', // Override folder
);
```

## Next Steps

- Set up [Automatic Cleanup](cleanup.md)
- Explore [Storage & Disks](storage.md)

