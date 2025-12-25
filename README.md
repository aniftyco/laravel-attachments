# Attachments for Laravel

> Turn any field on your Eloquent models into attachments with automatic file management, validation, and cleanup.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aniftyco/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/aniftyco/laravel-attachments)
[![Total Downloads](https://img.shields.io/packagist/dt/aniftyco/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/aniftyco/laravel-attachments)

## Features

- 🎯 **Simple API** - Easy-to-use casts for single and multiple attachments
- 🔄 **Automatic Cleanup** - Automatically delete files when models are deleted
- ✅ **File Validation** - Built-in validation for file size, type, and extensions
- 🔗 **URL Generation** - Generate public and temporary URLs for attachments
- 📦 **Multiple Storage Disks** - Support for any Laravel filesystem disk
- 🗂️ **Organized Storage** - Automatic folder organization with customizable paths
- 🔒 **Type Safe** - Full type hints and IDE autocomplete support

## Requirements

- PHP 8.3 or higher
- Laravel 12.0 or higher

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

Add attachment columns to your migrations using the provided Blueprint macros:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();

            // Single attachment
            $table->attachment('avatar');

            // Multiple attachments
            $table->attachments('photos');

            $table->timestamps();
        });
    }
};
```

The `attachment()` and `attachments()` macros automatically create nullable JSON columns for storing attachment data.

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
            $post->images->attach($image, folder: 'posts');
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

$attachment->name();      // File name
$attachment->disk();      // Storage disk name
$attachment->folder();    // Folder path
$attachment->path();      // Full path (folder/name) - alias for name()
$attachment->size();      // File size in bytes
$attachment->mimeType();  // MIME type
$attachment->extname();   // File extension (e.g., 'jpg')
$attachment->extension(); // Alias for extname()
$attachment->url();       // Public URL
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
    echo $image->url();
}

// Add a new attachment
$post->images->attach($file, folder: 'posts');

// Remove an attachment
$post->images = $post->images->filter(fn($img) => $img->name() !== 'old.jpg');
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
