# Upgrade Guide

This guide covers upgrading between major versions of Laravel Attachments.

## Upgrading to 1.0 from Pre-Release

Version 1.0 is the first stable release. If you were using a pre-release version, follow this guide to upgrade.

### What's New in 1.0

- Blueprint macros for easier migrations
- `HasAttachments` trait for fluent model API
- Collection operations (delete, move, copy, archive)
- Testing helpers and assertions
- API resources for JSON transformation
- Filament integration
- Enhanced metadata support
- Event system
- Improved documentation

### Breaking Changes

There are **no breaking changes** from pre-release versions. All existing code will continue to work.

### New Features

#### Blueprint Macros

**Before:**
```php
Schema::create('users', function (Blueprint $table) {
    $table->jsonb('avatar')->nullable();
});
```

**After (recommended):**
```php
Schema::create('users', function (Blueprint $table) {
    $table->attachment('avatar');
});
```

#### HasAttachments Trait

**New feature:**
```php
use NiftyCo\Attachments\Concerns\HasAttachments;

class User extends Model
{
    use HasAttachments;
    
    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
        ];
    }
}

// Now you can use:
$user->attachFile('avatar', $file, folder: 'avatars');
$user->clearAttachments('avatar', deleteFiles: true);
```

#### Collection Operations

**New feature:**
```php
// Delete all attachments
$post->images->delete();

// Move all attachments
$post->images->move('s3', 'archived');

// Copy all attachments
$post->images->copy('backup', 'backups');

// Archive attachments
$post->images->archive('images.zip');

// Get total size
$totalSize = $post->images->totalSize();

// Filter by type
$images = $post->images->ofType('image');
```

#### Testing Helpers

**New feature:**
```php
use NiftyCo\Attachments\Testing\InteractsWithAttachments;

class UserTest extends TestCase
{
    use InteractsWithAttachments;
    
    public function test_upload()
    {
        $attachment = $this->createFakeAttachment('image');
        $this->assertAttachmentExists($attachment);
    }
}
```

#### API Resources

**New feature:**
```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

return new AttachmentResource($user->avatar);
```

#### Filament Integration

**New feature:**
```php
use NiftyCo\Attachments\Filament\AttachmentField;
use NiftyCo\Attachments\Filament\AttachmentColumn;

AttachmentField::make('avatar')->images();
AttachmentColumn::make('avatar')->circular();
```

### Deprecations

#### `addFromFile()` Method

The `addFromFile()` method on `Attachments` collection is deprecated in favor of `attach()`:

**Before:**
```php
$post->images->addFromFile($file, folder: 'posts');
```

**After:**
```php
$post->images->attach($file, folder: 'posts');
```

**Note:** `addFromFile()` still works but will be removed in version 2.0.

### Recommended Updates

#### 1. Update Migrations

Use the new blueprint macros:

```php
// Old
$table->jsonb('avatar')->nullable();
$table->jsonb('images')->nullable();

// New
$table->attachment('avatar');
$table->attachments('images');
```

#### 2. Add HasAttachments Trait

For models with attachments:

```php
use NiftyCo\Attachments\Concerns\HasAttachments;

class User extends Model
{
    use HasAttachments;
}
```

#### 3. Update Collection Method Calls

Replace `addFromFile()` with `attach()`:

```php
// Old
$post->images->addFromFile($file, folder: 'posts');

// New
$post->images->attach($file, folder: 'posts');
```

#### 4. Add Testing Trait

In your test classes:

```php
use NiftyCo\Attachments\Testing\InteractsWithAttachments;

class UserTest extends TestCase
{
    use InteractsWithAttachments;
}
```

### Configuration Changes

No configuration changes are required. All existing configuration options remain the same.

### Database Changes

No database migrations are required. The package continues to use JSON columns for storing attachment data.

## Future Versions

### Version 2.0 (Planned)

Potential breaking changes in version 2.0:

- Removal of deprecated `addFromFile()` method
- Minimum PHP version may increase to 8.2
- Minimum Laravel version may increase to 12.0

We will provide advance notice and a detailed upgrade guide when version 2.0 is released.

## Getting Help

If you encounter issues during the upgrade:

1. Check the [documentation](index.md)
2. Search [GitHub Issues](https://github.com/aniftyco/laravel-attachments/issues)
3. Ask in [GitHub Discussions](https://github.com/aniftyco/laravel-attachments/discussions)

## Reporting Issues

If you find a bug or issue during the upgrade:

1. Check if it's already reported
2. Create a new issue with:
   - Laravel version
   - PHP version
   - Package version
   - Steps to reproduce
   - Expected vs actual behavior

