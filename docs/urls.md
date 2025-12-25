# URL Generation

Laravel Attachments provides flexible URL generation for accessing your files, supporting both public and private storage.

## Public URLs

For files stored on public disks, you can generate public URLs:

```php
// Get the URL
$url = $user->avatar->url;

// Or use the method
$url = $user->avatar->url();

// Example: https://example.com/storage/avatars/abc123.jpg
```

### Requirements

Public URLs require:

1. Files stored on a public disk (e.g., `public`, `s3` with public access)
2. For local public disk: symbolic link created with `php artisan storage:link`

### Configuration

Ensure your public disk is configured correctly:

```php
// config/filesystems.php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

## Temporary URLs

For files stored on private disks, generate temporary URLs with expiration:

```php
// Expires in 1 hour
$url = $user->document->temporaryUrl(now()->addHours(1));

// Expires in 30 minutes
$url = $user->document->temporaryUrl(now()->addMinutes(30));

// Using minutes directly
$url = $user->document->temporaryUrl(60); // 60 minutes
```

### Default Expiration

Set a default expiration time in configuration:

```php
// config/attachments.php
return [
    'temporary_url_expiration' => 60, // minutes
];
```

Or via environment variable:

```env
ATTACHMENTS_TEMPORARY_URL_EXPIRATION=60
```

### Supported Disks

Temporary URLs work with cloud storage providers:

- Amazon S3
- DigitalOcean Spaces
- Google Cloud Storage
- Cloudflare R2
- Any S3-compatible storage

**Note:** Local disks do not support temporary URLs.

## Download URLs

Generate download responses for files:

```php
// Download with original name
return $user->avatar->download();

// Download with custom filename
return $user->avatar->download('profile-picture.jpg');

// Force download (Content-Disposition: attachment)
return response()->download(
    Storage::disk($user->avatar->disk())->path($user->avatar->name()),
    'custom-name.jpg'
);
```

## CDN Integration

### Configuring CDN

For S3 or compatible services, configure a CDN URL:

```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_CDN_URL'), // CloudFront or CDN URL
],
```

Environment variable:

```env
AWS_CDN_URL=https://cdn.example.com
```

Now all URLs will use the CDN:

```php
$user->avatar->url;
// https://cdn.example.com/avatars/abc123.jpg
```

## URL Examples

### Public Disk (Local)

```php
$user->avatar = Attachment::fromFile($file, disk: 'public', folder: 'avatars');

echo $user->avatar->url;
// https://example.com/storage/avatars/abc123.jpg
```

### S3 with Public Access

```php
$user->avatar = Attachment::fromFile($file, disk: 's3', folder: 'avatars');

echo $user->avatar->url;
// https://bucket-name.s3.amazonaws.com/avatars/abc123.jpg
```

### S3 with CDN

```php
// With CDN configured
echo $user->avatar->url;
// https://cdn.example.com/avatars/abc123.jpg
```

### Private S3 with Temporary URL

```php
$user->document = Attachment::fromFile($file, disk: 's3-private', folder: 'docs');

echo $user->document->temporaryUrl(now()->addHour());
// https://bucket.s3.amazonaws.com/docs/abc123.pdf?X-Amz-Signature=...
```

## Using URLs in Views

### Blade Templates

```blade
{{-- Display image --}}
<img src="{{ $user->avatar->url }}" alt="Avatar">

{{-- Download link --}}
<a href="{{ $user->document->temporaryUrl(now()->addHour()) }}">
    Download Document
</a>

{{-- Conditional rendering --}}
@if($user->avatar)
    <img src="{{ $user->avatar->url }}" alt="Avatar">
@else
    <img src="/images/default-avatar.png" alt="Default Avatar">
@endif
```

### Multiple Attachments

```blade
<div class="gallery">
    @foreach($post->images as $image)
        <img src="{{ $image->url }}" alt="Post image">
    @endforeach
</div>
```

## API Resources

Transform attachments for JSON responses:

```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => new AttachmentResource($this->avatar),
        ];
    }
}
```

Response:

```json
{
  "id": 1,
  "name": "John Doe",
  "avatar": {
    "url": "https://example.com/storage/avatars/abc123.jpg",
    "name": "abc123.jpg",
    "size": 153600,
    "mime": "image/jpeg",
    "readable_size": "150 KB"
  }
}
```

## Security Considerations

### Public URLs

- Anyone with the URL can access the file
- Use for non-sensitive content only
- Consider adding authentication middleware for routes

### Temporary URLs

- URLs expire after the specified time
- Secure for sensitive documents
- Cannot be revoked before expiration
- Generate new URLs for each request

### Best Practices

```php
// Good: Private documents with temporary URLs
$invoice = Attachment::fromFile($file, disk: 's3-private', folder: 'invoices');
return $invoice->temporaryUrl(now()->addMinutes(15));

// Good: Public avatars with public URLs
$avatar = Attachment::fromFile($file, disk: 'public', folder: 'avatars');
return $avatar->url;

// Bad: Sensitive documents on public disk
$invoice = Attachment::fromFile($file, disk: 'public', folder: 'invoices');
return $invoice->url; // Anyone can access!
```

## Troubleshooting

### Public URLs Not Working

1. Check symbolic link exists:

```bash
php artisan storage:link
```

2. Verify disk configuration:

```php
'url' => env('APP_URL').'/storage',
```

3. Check file permissions:

```bash
chmod -R 755 storage/app/public
```

### Temporary URLs Not Working

1. Verify disk supports temporary URLs (S3, etc.)
2. Check AWS credentials are configured
3. Ensure bucket permissions allow signed URLs

### CDN URLs Not Applied

1. Verify `url` is set in disk configuration
2. Clear config cache:

```bash
php artisan config:clear
```

## Next Steps

- Learn about [API Resources](api-resources.md)
- Configure [Storage & Disks](storage.md)
- Explore [Metadata](metadata.md)
