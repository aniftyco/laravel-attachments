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
- 📊 **Bulk Operations** - Delete, move, copy, and archive multiple attachments at once
- 🧪 **Testing Helpers** - Comprehensive testing utilities and assertions
- 🎨 **Filament Integration** - Ready-to-use form fields and table columns
- 🌐 **API Resources** - Transform attachments for JSON responses
- 🛠️ **Database Helpers** - Blueprint macros for easy migration setup
- 🎭 **Model Trait** - Convenient methods for working with attachments

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

## Model Trait

The `HasAttachments` trait provides convenient methods for working with attachments on your models:

```php
use NiftyCo\Attachments\Concerns\HasAttachments;

class Post extends Model
{
    use HasAttachments;

    protected function casts(): array
    {
        return [
            'cover' => AsAttachment::class,
            'images' => AsAttachments::class,
        ];
    }
}
```

### Available Methods

```php
// Attach a single file
$post->attachFile('cover', $uploadedFile, disk: 'public', folder: 'covers');

// Attach multiple files
$post->attachFiles('images', [$file1, $file2, $file3], disk: 'public', folder: 'images');

// Add to existing collection
$post->addAttachment('images', $newFile, disk: 'public', folder: 'images');

// Remove attachment by name
$post->removeAttachment('images', 'old-photo.jpg');

// Clear all attachments (optionally delete files)
$post->clearAttachments('images', deleteFiles: true);

// Get all attachment attribute names
$attributes = $post->getAttachmentAttributes(); // ['cover', 'images']

// Check if model has any attachments
if ($post->hasAttachments()) {
    // Model has attachments
}

// Get total size of all attachments
$totalSize = $post->totalAttachmentsSize(); // in bytes
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

## Database Helpers

The package provides convenient Blueprint macros for creating attachment columns in your migrations:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');

    // Single attachment column
    $table->attachment('avatar');

    // Multiple attachments column
    $table->attachments('photos');

    $table->timestamps();
});
```

You can also specify custom column names:

```php
$table->attachment('profile_picture');
$table->attachments('gallery_images');
```

To drop attachment columns:

```php
Schema::table('users', function (Blueprint $table) {
    $table->dropAttachment('avatar');
    $table->dropAttachments('photos');
});
```

## Testing Helpers

The package includes a testing trait with helpful assertions and factory methods:

```php
use NiftyCo\Attachments\Testing\InteractsWithAttachments;

class UserTest extends TestCase
{
    use InteractsWithAttachments;

    public function test_user_can_upload_avatar()
    {
        $user = User::factory()->create();
        $attachment = $this->createFakeAttachment('avatar.jpg', 'public', 'avatars');

        $user->avatar = $attachment;
        $user->save();

        // Assert attachment exists in storage
        $this->assertAttachmentExists($user->avatar);

        // Assert attachment properties
        $this->assertAttachmentIsImage($user->avatar);
        $this->assertAttachmentMimeType($user->avatar, 'image/jpeg');

        // Assert metadata
        $user->avatar->withMeta('author', 'John');
        $this->assertAttachmentHasMeta($user->avatar, 'author', 'John');
    }
}
```

### Available Assertions

```php
$this->assertAttachmentExists($attachment);
$this->assertAttachmentMissing($attachment);
$this->assertAttachmentContent($attachment, 'expected content');
$this->assertAttachmentSize($attachment, 1024);
$this->assertAttachmentMimeType($attachment, 'image/jpeg');
$this->assertAttachmentIsImage($attachment);
$this->assertAttachmentIsPdf($attachment);
$this->assertAttachmentHasMeta($attachment, 'key', 'value');
```

### Factory Methods

```php
// Create a single fake attachment
$attachment = $this->createFakeAttachment('test.jpg', 'public', 'test', 100);

// Create multiple fake attachments
$attachments = $this->createFakeAttachments(5, 'public', 'test');
```

## API Resources

Transform attachments for API responses using the provided resource classes:

```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;
use NiftyCo\Attachments\Http\Resources\AttachmentCollection;

// Single attachment
return new AttachmentResource($user->avatar);

// Multiple attachments
return new AttachmentCollection($post->images);
```

### Resource Output

```json
{
  "name": "photo.jpg",
  "path": "photos/photo.jpg",
  "url": "https://example.com/storage/photos/photo.jpg",
  "size": 102400,
  "readable_size": "100.00 KB",
  "mime": "image/jpeg",
  "extension": "jpg",
  "disk": "public",
  "folder": "photos",
  "metadata": {
    "author": "John Doe"
  },
  "created_at": "2024-01-15T10:30:00.000000Z",
  "updated_at": "2024-01-15T10:30:00.000000Z",
  "type": "image"
}
```

### Collection Output

```json
{
  "data": [
    {
      /* attachment resource */
    },
    {
      /* attachment resource */
    }
  ],
  "meta": {
    "total": 2,
    "total_size": 204800,
    "total_readable_size": "200.00 KB"
  }
}
```

## Filament Integration

The package provides Filament form fields and table columns for easy integration:

### Form Fields

```php
use NiftyCo\Attachments\Filament\AttachmentField;

// Single attachment
AttachmentField::make('avatar')
    ->attachmentDisk('public')
    ->attachmentFolder('avatars')
    ->images()
    ->maxSize(5120);

// Multiple attachments
AttachmentField::multiple('photos')
    ->attachmentDisk('public')
    ->attachmentFolder('photos')
    ->images()
    ->maxFiles(10);

// Document uploads
AttachmentField::make('resume')
    ->documents()
    ->maxSize(10240);
```

### Table Columns

```php
use NiftyCo\Attachments\Filament\AttachmentColumn;

// Display as image
AttachmentColumn::make('avatar')
    ->circular();

// Display as text (filename)
AttachmentColumn::make('document')
    ->asText();

// Display as size
AttachmentColumn::make('file')
    ->asSize();

// Make downloadable
AttachmentColumn::make('resume')
    ->downloadable();
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
