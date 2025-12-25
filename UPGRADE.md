# Upgrade Guide

This guide will help you upgrade between major versions of the Attachments for Laravel package.

## Upgrading to 1.0.0 from Pre-release

Version 1.0.0 is the first stable release. If you were using a pre-release version, please review the following changes:

### Configuration Changes

The configuration file has been updated with new options. Publish the latest configuration:

```sh
php artisan vendor:publish --tag=attachments-config --force
```

Review the new configuration options:
- `events.enabled` - Control event dispatching
- `validation` - Default validation rules

### New Features

#### Blueprint Macros

You can now use convenient Blueprint macros in migrations:

```php
// Old way
$table->json('avatar')->nullable();

// New way (recommended)
$table->attachment('avatar');
$table->attachments('photos');
```

#### Model Trait

The new `HasAttachments` trait provides convenient methods:

```php
use NiftyCo\Attachments\Concerns\HasAttachments;

class User extends Model
{
    use HasAttachments;
    
    // Now you can use:
    // $user->attachFile('avatar', $file);
    // $user->hasAttachments();
    // etc.
}
```

#### Collection Operations

New bulk operations on `Attachments` collections:

```php
$post->images->delete();
$post->images->move('s3', 'archived');
$post->images->copy('backup', 'backups');
$post->images->archive('images.zip');
$post->images->totalSize();
$post->images->ofType('image');
```

#### Testing Helpers

New testing trait with assertions:

```php
use NiftyCo\Attachments\Testing\InteractsWithAttachments;

class UserTest extends TestCase
{
    use InteractsWithAttachments;
    
    public function test_upload()
    {
        $attachment = $this->createFakeAttachment();
        $this->assertAttachmentExists($attachment);
    }
}
```

#### API Resources

Transform attachments for JSON responses:

```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

return new AttachmentResource($user->avatar);
```

#### Filament Integration

Ready-to-use Filament components:

```php
use NiftyCo\Attachments\Filament\AttachmentField;
use NiftyCo\Attachments\Filament\AttachmentColumn;

AttachmentField::make('avatar')->images();
AttachmentColumn::make('avatar')->circular();
```

### Breaking Changes

There are no breaking changes in 1.0.0 from the pre-release versions. All existing code should continue to work.

### Deprecations

- `Attachments::addFromFile()` is deprecated in favor of `Attachments::attach()`. Both methods work identically, but `attach()` is the preferred method going forward.

## Future Upgrades

This section will be updated with upgrade instructions for future major versions.

### Upgrading to 2.0.0 (Future)

_This section will be populated when version 2.0.0 is released._

