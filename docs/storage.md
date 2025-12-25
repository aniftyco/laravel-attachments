# Storage & Disks

Laravel Attachments works with any Laravel filesystem disk, giving you flexibility in where and how you store files.

## Configuring Disks

### Default Disk

Set the default disk in your configuration:

```php
// config/attachments.php
return [
    'disk' => env('ATTACHMENTS_DISK', 'public'),
];
```

Or in your `.env` file:

```env
ATTACHMENTS_DISK=public
```

### Available Disks

You can use any disk defined in `config/filesystems.php`:

#### Local Disk (Private)

```php
// config/filesystems.php
'local' => [
    'driver' => 'local',
    'root' => storage_path('app'),
],
```

Files are stored in `storage/app` and are not publicly accessible.

#### Public Disk

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

Files are stored in `storage/app/public` and accessible via `/storage` URL.

**Don't forget to create the symbolic link:**

```bash
php artisan storage:link
```

#### Amazon S3

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

#### Other Cloud Storage

Laravel supports many cloud storage providers:
- DigitalOcean Spaces
- Google Cloud Storage
- Azure Blob Storage
- Cloudflare R2
- And more...

## Using Different Disks

### Per-Attachment Disk

Override the disk when creating an attachment:

```php
use NiftyCo\Attachments\Attachment;

// Store on S3
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    disk: 's3',
    folder: 'avatars'
);

// Store locally
$user->document = Attachment::fromFile(
    $request->file('document'),
    disk: 'local',
    folder: 'documents'
);
```

### Multiple Disks in One Model

```php
class User extends Model
{
    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,    // Uses default disk
            'document' => AsAttachment::class,  // Uses default disk
        ];
    }
}

// Store avatar on public disk
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    disk: 'public',
    folder: 'avatars'
);

// Store document on private disk
$user->document = Attachment::fromFile(
    $request->file('document'),
    disk: 'local',
    folder: 'documents'
);
```

## Folder Organization

### Default Folder

Set the default folder in configuration:

```php
// config/attachments.php
return [
    'folder' => env('ATTACHMENTS_FOLDER', 'attachments'),
];
```

### Custom Folders

Organize files by type, user, or any other criteria:

```php
// By type
$user->avatar = Attachment::fromFile($file, folder: 'avatars');
$post->image = Attachment::fromFile($file, folder: 'posts');

// By user ID
$folder = 'users/' . auth()->id();
$user->avatar = Attachment::fromFile($file, folder: $folder);

// By date
$folder = 'uploads/' . now()->format('Y/m');
$attachment = Attachment::fromFile($file, folder: $folder);

// Nested folders
$user->avatar = Attachment::fromFile($file, folder: 'users/avatars');
```

## File Visibility

### Public Files

Files on public disks are accessible via URL:

```php
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    disk: 'public',
    folder: 'avatars'
);

// Access via URL
echo $user->avatar->url; // https://example.com/storage/avatars/abc123.jpg
```

### Private Files

Files on private disks require temporary URLs:

```php
$user->document = Attachment::fromFile(
    $request->file('document'),
    disk: 'local',
    folder: 'documents'
);

// Generate temporary URL (requires S3 or compatible disk)
$url = $user->document->temporaryUrl(now()->addHours(1));
```

## Moving Attachments

### Move to Different Disk

```php
// Move from public to S3
$newAttachment = $user->avatar->move('s3', 'archived-avatars');
$user->avatar = $newAttachment;
$user->save();
```

### Move to Different Folder

```php
// Move to different folder on same disk
$newAttachment = $user->avatar->move(folder: 'old-avatars');
$user->avatar = $newAttachment;
$user->save();
```

## Copying Attachments

### Copy to Different Disk

```php
// Create a backup on S3
$backup = $user->avatar->copy('s3', 'backups/avatars');
```

### Copy to Different Folder

```php
// Create a copy in different folder
$copy = $user->avatar->copy(folder: 'avatar-copies');
```

## Storage Best Practices

### 1. Use Appropriate Disks

- **Public disk**: For publicly accessible files (avatars, product images)
- **Private disk**: For sensitive documents (invoices, contracts)
- **Cloud storage**: For scalability and CDN integration

### 2. Organize with Folders

```php
// Good: Organized by type and date
$folder = 'invoices/' . now()->format('Y/m');

// Bad: Everything in one folder
$folder = 'files';
```

### 3. Use Environment Variables

```env
ATTACHMENTS_DISK=s3
ATTACHMENTS_FOLDER=production/attachments
```

### 4. Consider CDN Integration

For S3 or compatible services, configure a CDN:

```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'url' => env('AWS_CDN_URL'), // CloudFront URL
    // ...
],
```

### 5. Implement Backup Strategy

```php
// Automatically backup important files
$user->document = Attachment::fromFile($file, disk: 'local', folder: 'documents');
$user->document->copy('s3', 'backups/documents');
```

## Checking Storage

### Check if File Exists

```php
if ($user->avatar->exists()) {
    // File exists in storage
}
```

### Get File Size

```php
$bytes = $user->avatar->size;
$readable = $user->avatar->readableSize(); // "1.5 MB"
```

### Get Storage Path

```php
$path = $user->avatar->path(); // "avatars/abc123.jpg"
$disk = $user->avatar->disk;   // "public"
```

## Next Steps

- Learn about [URL Generation](urls.md)
- Configure [Automatic Cleanup](cleanup.md)
- Explore [Metadata](metadata.md)

