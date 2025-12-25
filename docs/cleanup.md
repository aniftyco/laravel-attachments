# Automatic Cleanup

Laravel Attachments provides automatic file cleanup to prevent orphaned files from accumulating in your storage.

## Overview

The package offers two types of automatic cleanup:

1. **Model Deletion Cleanup** - Delete files when the parent model is deleted
2. **Replacement Cleanup** - Delete old files when attachments are replaced

## Model Deletion Cleanup

### Enabling Cleanup

Add the `HasAttachmentCleanup` trait to your model:

```php
use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Casts\AsAttachment;
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

Now when you delete a user, their avatar file is automatically deleted:

```php
$user->delete(); // Avatar file is automatically deleted from storage
```

### How It Works

The trait registers a model observer that:
1. Detects when a model is being deleted
2. Finds all attachment attributes (using casts)
3. Deletes the associated files from storage
4. Proceeds with model deletion

### Multiple Attachments

The cleanup works with both single and multiple attachments:

```php
use NiftyCo\Attachments\Casts\AsAttachments;
use NiftyCo\Attachments\Concerns\HasAttachmentCleanup;

class Post extends Model
{
    use HasAttachmentCleanup;

    protected function casts(): array
    {
        return [
            'images' => AsAttachments::class,
        ];
    }
}

// All images are deleted when post is deleted
$post->delete();
```

### Soft Deletes

Cleanup respects soft deletes:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use NiftyCo\Attachments\Concerns\HasAttachmentCleanup;

class User extends Model
{
    use SoftDeletes, HasAttachmentCleanup;

    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
        ];
    }
}

// Soft delete - files are NOT deleted
$user->delete();

// Force delete - files ARE deleted
$user->forceDelete();

// Restore - files remain intact
$user->restore();
```

### Disabling Cleanup Globally

Disable automatic cleanup in the configuration:

```php
// config/attachments.php
return [
    'auto_cleanup' => false,
];
```

Or via environment variable:

```env
ATTACHMENTS_AUTO_CLEANUP=false
```

### Disabling Cleanup Per Model

If you want cleanup disabled for a specific model, don't add the trait:

```php
class User extends Model
{
    // No HasAttachmentCleanup trait
    
    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
        ];
    }
}

// Files are NOT automatically deleted
$user->delete();
```

## Replacement Cleanup

### How It Works

By default, when you replace an attachment with a new one, the old file is automatically deleted:

```php
// User has an existing avatar
$user->avatar; // "old-avatar.jpg"

// Replace with new avatar - old file is automatically deleted
$user->avatar = Attachment::fromFile($newFile, folder: 'avatars');
$user->save();
```

### Disabling Replacement Cleanup

Disable globally in configuration:

```php
// config/attachments.php
return [
    'delete_on_replace' => false,
];
```

Or via environment variable:

```env
ATTACHMENTS_DELETE_ON_REPLACE=false
```

### Multiple Attachments Replacement

When working with collections, you control what gets deleted:

```php
// Add to existing collection (no deletions)
$post->images->attach($newFile, folder: 'posts');
$post->save();

// Replace entire collection (old files deleted if delete_on_replace is true)
$post->images = Attachments::fromFiles($newFiles, folder: 'posts');
$post->save();

// Remove specific files
$post->images = $post->images->filter(fn($img) => $img->name !== 'old.jpg');
$post->save();
```

## Manual Cleanup

### Delete Single Attachment

```php
// Delete the file from storage
$user->avatar->delete();

// Clear the attribute
$user->avatar = null;
$user->save();
```

### Delete Multiple Attachments

```php
// Delete all files
$post->images->delete();

// Clear the attribute
$post->images = new Attachments();
$post->save();
```

### Using HasAttachments Trait

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

// Clear attachments with file deletion
$user->clearAttachments('avatar', deleteFiles: true);
$user->save();
```

## Cleanup Strategies

### Strategy 1: Automatic (Recommended)

Use both traits for full automation:

```php
use NiftyCo\Attachments\Concerns\HasAttachmentCleanup;
use NiftyCo\Attachments\Concerns\HasAttachments;

class User extends Model
{
    use HasAttachmentCleanup, HasAttachments;
}
```

**Pros:**
- No manual cleanup needed
- Prevents orphaned files
- Works automatically

**Cons:**
- Files are permanently deleted
- No recovery after deletion

### Strategy 2: Manual

Don't use the cleanup trait:

```php
class User extends Model
{
    // No cleanup trait
}
```

**Pros:**
- Full control over file deletion
- Can implement custom cleanup logic
- Files preserved after model deletion

**Cons:**
- Must manually delete files
- Risk of orphaned files
- Requires cleanup jobs/commands

### Strategy 3: Scheduled Cleanup

Disable automatic cleanup and use a scheduled job:

```php
// config/attachments.php
return [
    'auto_cleanup' => false,
    'delete_on_replace' => false,
];
```

Create a cleanup command:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedFiles extends Command
{
    protected $signature = 'attachments:cleanup';

    public function handle()
    {
        // Your cleanup logic
        // Find files not referenced in database
        // Delete orphaned files
    }
}
```

Schedule it:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('attachments:cleanup')->daily();
}
```

## Best Practices

### 1. Use Automatic Cleanup for Most Cases

```php
use HasAttachmentCleanup;
```

### 2. Be Careful with Soft Deletes

Files are only deleted on force delete:

```php
$user->delete();       // Files kept
$user->forceDelete();  // Files deleted
```

### 3. Test Cleanup Behavior

```php
public function test_avatar_deleted_with_user()
{
    $user = User::factory()->create();
    $user->avatar = Attachment::fromFile($file, folder: 'avatars');
    $user->save();

    $path = $user->avatar->path();
    
    $user->delete();
    
    $this->assertFalse(Storage::disk('public')->exists($path));
}
```

### 4. Consider Backup Before Cleanup

```php
// Backup before deleting
$user->avatar->copy('s3', 'backups/avatars');
$user->delete();
```

## Next Steps

- Learn about [Events](events.md)
- Explore [Testing](testing.md)
- Configure [Storage](storage.md)

