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
'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'public')),
```

**Environment Variable:**
```env
ATTACHMENTS_DISK=public
```

You can use any disk defined in `config/filesystems.php`:
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

### File Validation Rules

Default validation rules applied to all file uploads:

```php
'validation' => [
    'file',
    'max:10240', // 10MB
    'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,zip,rar',
],
```

You can specify rules as an array or pipe-separated string:

```php
// Array format
'validation' => ['file', 'image', 'max:2048'],

// String format
'validation' => 'file|image|max:2048',

// Disable validation
'validation' => null,
```

**Common validation rules:**
- `file` - Must be a successfully uploaded file
- `image` - Must be an image (jpeg, png, bmp, gif, svg, webp)
- `max:2048` - Maximum size in kilobytes
- `min:100` - Minimum size in kilobytes
- `mimes:jpg,png,pdf` - Allowed file extensions
- `mimetypes:image/jpeg,image/png` - Allowed MIME types
- `dimensions:min_width=100,min_height=100` - Image dimension constraints

### File Naming Strategy

Configure how uploaded files are named:

```php
'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'hash'),
```

**Environment Variable:**
```env
ATTACHMENTS_NAMING_STRATEGY=hash
```

**Available strategies:**
- `hash` (default) - Use Laravel's hash-based naming
- `original` - Keep the original filename (sanitized)
- `uuid` - Generate a UUID for the filename

### Preserve Original Filename

Store the original filename in metadata:

```php
'preserve_original_name' => env('ATTACHMENTS_PRESERVE_ORIGINAL_NAME', true),
```

**Environment Variable:**
```env
ATTACHMENTS_PRESERVE_ORIGINAL_NAME=true
```

When enabled, the original filename is stored even when using hash or UUID naming strategies.

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

### Events

Enable or disable attachment lifecycle events:

```php
'events' => [
    'enabled' => env('ATTACHMENTS_EVENTS_ENABLED', true),
],
```

**Environment Variable:**
```env
ATTACHMENTS_EVENTS_ENABLED=true
```

When enabled, events are dispatched for file uploads, deletions, etc.

### Metadata

Configure metadata storage for attachments:

```php
'metadata' => [
    'enabled' => env('ATTACHMENTS_METADATA_ENABLED', true),
    
    'auto_capture' => [
        'original_name' => true,
        'uploaded_at' => true,
        'uploaded_by' => false, // Requires authentication
    ],
],
```

**Environment Variables:**
```env
ATTACHMENTS_METADATA_ENABLED=true
```

## Per-Attachment Configuration

You can override most configuration options when creating attachments:

```php
use NiftyCo\Attachments\Attachment;

$attachment = Attachment::fromFile(
    $file,
    disk: 's3',                    // Override disk
    folder: 'user-uploads',        // Override folder
    validate: ['image', 'max:5120'] // Override validation
);
```

## Next Steps

- Learn about [File Validation](validation.md)
- Set up [Automatic Cleanup](cleanup.md)
- Explore [Storage & Disks](storage.md)

