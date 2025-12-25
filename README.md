# Attachments for Laravel

> Turn any field on your Eloquent models into attachments with automatic file management, validation, and cleanup.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aniftyco/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/aniftyco/laravel-attachments)
[![Total Downloads](https://img.shields.io/packagist/dt/aniftyco/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/aniftyco/laravel-attachments)

> [!WARNING]
> This package is not ready for general consumption

## Features

- 🎯 **Simple API** - Easy-to-use casts for single and multiple attachments
- 🔄 **Automatic Cleanup** - Automatically delete files when models are deleted
- ✅ **File Validation** - Fluent validation rule with built-in file type checking
- 🔗 **URL Generation** - Generate public and temporary URLs for attachments
- 📦 **Multiple Storage Disks** - Support for any Laravel filesystem disk
- 🗂️ **Organized Storage** - Automatic folder organization with customizable paths
- 🔒 **Type Safe** - Full type hints and IDE autocomplete support
- 📡 **Events** - Listen to attachment lifecycle events (created, updated, deleted)
- 🏷️ **Metadata** - Store custom metadata with attachments
- 🔧 **File Operations** - Move, rename, duplicate, and manage attachments easily

## Installation

You can install the package via Composer:

```sh
composer require aniftyco/laravel-attachments
```

Publish the configuration file (optional):

```sh
php artisan vendor:publish --tag=attachments-config
```

## Usage

### Migrations

Your migrations need to have a `Blueprint::jsonb()` column set on it.

```php
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            //...

            $table->jsonb('avatar')->nullable();
        });
    }
};
```

### Adding Attachments to Models

To add attachments to your Eloquent models, use the provided cast classes.

#### Single Attachment

Use the `AsAttachment` cast to handle a single attachment:

```php
use NiftyCo\Attachments\Casts\AsAttachment;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
        ];
    }
}
```

To set an image as an attachment on your model:

```php
use NiftyCo\Attachments\Attachment;

class UserController
{
    public function store(UserStoreRequest $request, User $user)
    {
        $user->avatar = Attachment::fromFile($request->file('avatar'), folder: 'avatars');

        $user->save();

        // ...
    }
}
```

#### Multiple Attachments

Use the `AsAttachments` cast to handle multiple attachments:

```php
use NiftyCo\Attachments\Casts\AsAttachments;

class Post extends Model
{
    protected function casts(): array
    {
        return [
            'images' => AsAttachments::class,
        ];
    }
}
```

To attach multiple attachments to your model:

```php
use NiftyCo\Attachments\Attachments;

class PostController
{
    public function store(PostStoreRequest $request, Post $post)
    {
        // Create from multiple files at once
        $post->images = Attachments::fromFiles($request->file('images'), folder: 'posts');

        // Or add files one at a time
        foreach ($request->file('images') as $image) {
            $post->images->addFromFile($image, folder: 'posts');
        }

        $post->save();
    }
}
```

### File Validation

You can validate files during upload by passing validation rules:

```php
use NiftyCo\Attachments\Attachment;

// Single attachment with validation
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars',
    validate: ['image', 'max:2048', 'mimes:jpg,png']
);

// Multiple attachments with validation
$post->images = Attachments::fromFiles(
    $request->file('images'),
    folder: 'posts',
    validate: ['image', 'max:5120']
);
```

Validation rules can be:

- **Array format**: `['image', 'max:2048', 'mimes:jpg,png']`
- **String format**: `'image|max:2048|mimes:jpg,png'`

Common validation rules:

- `image` - Must be an image (jpeg, png, bmp, gif, svg, or webp)
- `max:2048` - Maximum file size in kilobytes
- `mimes:jpg,png,pdf` - Allowed file extensions
- `mimetypes:image/jpeg,image/png` - Allowed MIME types

### Working with Attachments

#### Accessing Attachment Properties

```php
$attachment = $user->avatar;

$attachment->name;      // File name
$attachment->disk;      // Storage disk name
$attachment->folder;    // Folder path
$attachment->path();    // Full path (folder/name)
$attachment->size;      // File size in bytes
$attachment->mime;      // MIME type
$attachment->url;       // Public URL
```

#### Generating URLs

```php
// Public URL (for public disks)
$url = $user->avatar->url();

// Temporary URL (for private disks)
$url = $user->avatar->temporaryUrl(now()->addHours(1));

// Or use minutes
$url = $user->avatar->temporaryUrl(60); // 60 minutes
```

#### File Operations

```php
// Check if file exists
if ($user->avatar->exists()) {
    // File exists
}

// Get file contents
$contents = $user->avatar->contents();

// Download file
return $user->avatar->download();
return $user->avatar->download('custom-filename.jpg');

// Get human-readable file size
$size = $user->avatar->readableSize(); // "1.5 MB"

// Delete attachment
$user->avatar->delete();
```

#### Working with Multiple Attachments

```php
// Count attachments
$count = $post->images->count();

// Loop through attachments
foreach ($post->images as $image) {
    echo $image->url;
}

// Add a new attachment
$post->images->addFromFile($file, folder: 'posts');

// Remove an attachment
$post->images = $post->images->filter(fn($img) => $img->name !== 'old.jpg');
$post->save();
```

## Automatic File Cleanup

The package provides automatic file cleanup when models are deleted or attachments are replaced.

### Model Deletion Cleanup

To enable automatic cleanup when a model is deleted, add the `HasAttachmentCleanup` trait to your model:

```php
use NiftyCo\Attachments\Concerns\HasAttachmentCleanup;

class User extends Model
{
    use HasAttachmentCleanup;

    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
        ];
    }
}
```

Now when you delete a user, their avatar file will be automatically deleted:

```php
$user->delete(); // Avatar file is automatically deleted from storage
```

### Replacement Cleanup

By default, when you replace an attachment with a new one, the old file is automatically deleted:

```php
// Old avatar file is automatically deleted when replaced
$user->avatar = Attachment::fromFile($newFile, folder: 'avatars');
$user->save();
```

### Disabling Automatic Cleanup

You can disable automatic cleanup globally in the configuration file:

```php
// config/attachments.php

return [
    // Disable cleanup when models are deleted
    'auto_cleanup' => false,

    // Disable cleanup when attachments are replaced
    'delete_on_replace' => false,
];
```

## Validation

The package provides a fluent validation rule for easy file validation in form requests:

```php
use NiftyCo\Attachments\Rules\AttachmentRule;

class UserStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => ['required', AttachmentRule::make()->images()->maxSizeMb(5)],
            'resume' => ['required', AttachmentRule::make()->mimes(['pdf', 'doc', 'docx'])->maxSizeMb(10)],
            'photos' => ['required', 'array'],
            'photos.*' => [AttachmentRule::make()->images()->maxSizeMb(2)],
        ];
    }
}
```

### Available Validation Methods

```php
AttachmentRule::make()
    ->maxSize(1024)           // Max size in kilobytes
    ->maxSizeKb(1024)         // Max size in kilobytes
    ->maxSizeMb(5)            // Max size in megabytes
    ->mimes(['jpg', 'png'])   // Allowed MIME types
    ->extensions(['jpg', 'png']) // Allowed extensions
    ->images()                // Shorthand for common image types
    ->documents();            // Shorthand for common document types
```

### Validation Helper Methods

The `Attachment` class also provides helper methods for checking file types:

```php
if ($user->avatar->isImage()) {
    // It's an image
}

if ($document->file->isPdf()) {
    // It's a PDF
}

// Available methods:
$attachment->isImage();    // jpg, jpeg, png, gif, webp, svg
$attachment->isPdf();      // pdf
$attachment->isVideo();    // mp4, mov, avi, wmv, flv, webm
$attachment->isAudio();    // mp3, wav, ogg, flac, aac
$attachment->isDocument(); // pdf, doc, docx, xls, xlsx, ppt, pptx
```

### Filename Sanitization

For security, you can sanitize filenames before storing:

```php
use NiftyCo\Attachments\Attachment;

$safeName = Attachment::sanitizeFilename($unsafeName);
// Removes special characters, spaces, and potential security risks
```

## Events

The package dispatches events for attachment operations, allowing you to hook into the attachment lifecycle:

### Available Events

```php
use NiftyCo\Attachments\Events\AttachmentCreated;
use NiftyCo\Attachments\Events\AttachmentUpdated;
use NiftyCo\Attachments\Events\AttachmentDeleted;
```

### Event Properties

All events contain the following properties:

```php
class AttachmentCreated
{
    public function __construct(
        public Attachment $attachment,  // The attachment instance
        public string $modelClass,      // The model class name
        public mixed $modelId,          // The model ID
        public string $attribute        // The attribute name
    ) {}
}

class AttachmentUpdated
{
    public function __construct(
        public Attachment $attachment,     // The new attachment
        public ?Attachment $oldAttachment, // The old attachment (if any)
        public string $modelClass,
        public mixed $modelId,
        public string $attribute
    ) {}
}

class AttachmentDeleted
{
    public function __construct(
        public Attachment $attachment,
        public string $modelClass,
        public mixed $modelId,
        public string $attribute
    ) {}
}
```

### Listening to Events

Create a listener for attachment events:

```php
namespace App\Listeners;

use NiftyCo\Attachments\Events\AttachmentCreated;

class ProcessUploadedAttachment
{
    public function handle(AttachmentCreated $event): void
    {
        // Process the uploaded attachment
        $attachment = $event->attachment;
        $modelClass = $event->modelClass;
        $modelId = $event->modelId;

        // Example: Generate thumbnails for images
        if ($attachment->isImage()) {
            // Generate thumbnail...
        }

        // Example: Scan for viruses
        // VirusScanner::scan($attachment->path());

        // Example: Log the upload
        Log::info("Attachment uploaded", [
            'model' => $modelClass,
            'id' => $modelId,
            'file' => $attachment->name,
        ]);
    }
}
```

Register the listener in your `EventServiceProvider`:

```php
use NiftyCo\Attachments\Events\AttachmentCreated;
use App\Listeners\ProcessUploadedAttachment;

protected $listen = [
    AttachmentCreated::class => [
        ProcessUploadedAttachment::class,
    ],
];
```

### Disabling Events

You can disable events globally in the configuration:

```php
// config/attachments.php

return [
    'events' => [
        'enabled' => false,
    ],
];
```

## Configuration

The package comes with sensible defaults, but you can customize the behavior by publishing and editing the configuration file:

```sh
php artisan vendor:publish --tag=attachments-config
```

Available configuration options:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The default filesystem disk to use for storing attachments.
    | This should match one of the disks defined in config/filesystems.php
    |
    */
    'disk' => env('ATTACHMENTS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Default Folder
    |--------------------------------------------------------------------------
    |
    | The default folder path where attachments will be stored.
    | You can override this per-attachment when creating them.
    |
    */
    'folder' => env('ATTACHMENTS_FOLDER', 'attachments'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Cleanup
    |--------------------------------------------------------------------------
    |
    | When enabled, attachment files will be automatically deleted from storage
    | when the parent model is deleted. Requires the HasAttachmentCleanup trait.
    |
    */
    'auto_cleanup' => env('ATTACHMENTS_AUTO_CLEANUP', true),

    /*
    |--------------------------------------------------------------------------
    | Delete on Replace
    |--------------------------------------------------------------------------
    |
    | When enabled, the old attachment file will be automatically deleted
    | when it's replaced with a new attachment.
    |
    */
    'delete_on_replace' => env('ATTACHMENTS_DELETE_ON_REPLACE', true),

    /*
    |--------------------------------------------------------------------------
    | Temporary URL Expiration
    |--------------------------------------------------------------------------
    |
    | The default expiration time (in minutes) for temporary URLs.
    | This is used when calling temporaryUrl() without an expiration parameter.
    |
    */
    'temporary_url_expiration' => env('ATTACHMENTS_TEMP_URL_EXPIRATION', 60),

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable events for attachment operations.
    | When enabled, events will be dispatched for file uploads, deletions, etc.
    |
    */
    'events' => [
        'enabled' => env('ATTACHMENTS_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    |
    | Default validation rules for uploaded files.
    | These rules will be applied when using Attachment::fromFile()
    |
    */
    'validation' => [
        'file',
        'max:10240', // 10MB
        'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,zip,rar',
    ],
];
```

## Testing

Run the test suite:

```sh
composer test
```

Run code style checks:

```sh
composer lint
```

Fix code style issues:

```sh
./vendor/bin/pint
```

## Contributing

Thank you for considering contributing to the Attachments for Laravel package! You can read the contribution guide [here](CONTRIBUTING.md).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
