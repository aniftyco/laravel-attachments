# Single Attachments

Single attachments are perfect for handling one file per model attribute, such as user avatars, profile pictures, or document uploads.

## Setup

### Migration

Create an attachment column in your migration:

```php
use Illuminate\Database\Schema\Blueprint;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->attachment('avatar');
    $table->timestamps();
});
```

The `attachment()` macro creates a nullable JSON column for storing attachment data.

### Model

Add the `AsAttachment` cast to your model:

```php
use Illuminate\Database\Eloquent\Model;
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

## Creating Attachments

### From Uploaded File

```php
use NiftyCo\Attachments\Attachment;

$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars'
);
$user->save();
```

### With Custom Disk

```php
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    disk: 's3',
    folder: 'user-avatars'
);
$user->save();
```

### From Raw Content

When you have the file's bytes rather than an upload, use `fromContent()`. The filename is optional and only feeds the naming strategy and extension. It is never persisted:

```php
$user->avatar = Attachment::fromContent(
    $pdfBytes,
    filename: 'invoice.pdf',
    folder: 'invoices'
);
$user->save();
```

### From a Remote URL

Use `fromUrl()` to stream a remote file straight into the disk (without buffering
it in memory). The extension is derived from the URL and the MIME type from the
response `Content-Type`:

```php
$user->avatar = Attachment::fromUrl(
    'https://example.com/avatars/jane.png',
    folder: 'avatars'
);
$user->save();
```

> **Security:** `fromUrl()` fetches whatever URL you give it. Validating or
> allow-listing the host to prevent SSRF is your responsibility.

### From a Stream

Use `fromStream()` for large sources you don't want to buffer in memory. You own
the stream and must close it yourself:

```php
$stream = fopen('/path/to/large-export.csv', 'r');
$user->export = Attachment::fromStream($stream, 'export.csv', folder: 'exports');
fclose($stream);
$user->save();
```

## Using the HasAttachments Trait

For a more fluent API, use the `HasAttachments` trait:

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
```

Now you can use helper methods:

```php
// Assign a file (the cast handles storage)
$user->avatar = Attachment::fromFile($request->file('avatar'), folder: 'avatars');
$user->save();

// Clear an attachment
$user->clearAttachments('avatar', deleteFiles: true);
$user->save();
```

## Accessing Attachment Properties

```php
$avatar = $user->avatar;

// Basic properties
$avatar->path();      // File path in storage (e.g., "avatars/abc123.jpg")
$avatar->disk();      // Storage disk (e.g., "public")
$avatar->folder();    // Folder path (e.g., "avatars")
$avatar->size();      // File size in bytes
$avatar->mime();      // MIME type (e.g., "image/jpeg")
$avatar->extension(); // File extension (e.g., "jpg")

// URLs
$avatar->url();     // Public URL

// Human-readable size
$avatar->readableSize(); // "1.5 MB"
```

## File Operations

### Check if File Exists

```php
if ($user->avatar->exists()) {
    // File exists in storage
}
```

### Get File Contents

```php
$contents = $user->avatar->contents();
```

### Download File

```php
// Download with original name
return $user->avatar->download();

// Download with custom name
return $user->avatar->download('profile-picture.jpg');
```

### Delete File

```php
$user->avatar->delete();
```

## Type Checking

```php
// Check if attachment is an image
if ($user->avatar->isImage()) {
    // It's an image
}

// Check if attachment is a PDF
if ($user->avatar->isPdf()) {
    // It's a PDF
}

// Check if attachment is a video
if ($user->avatar->isVideo()) {
    // It's a video
}

// Check if attachment is an audio file
if ($user->avatar->isAudio()) {
    // It's an audio file
}
```

## Replacing Attachments

When you replace an attachment, the old file is automatically deleted on save (if `delete_on_replace` is enabled and the model uses the `HasAttachments` trait):

```php
// The old avatar's file is purged when the replacement is saved
$user->avatar = Attachment::fromFile($newFile, folder: 'avatars');
$user->save();
```

## Null Handling

Attachments can be null:

```php
// Check if attachment exists
if ($user->avatar) {
    echo $user->avatar->url();
}

// Set to null
$user->avatar = null;
$user->save();
```

## Next Steps

- Learn about [Multiple Attachments](multiple-attachments.md)
- Generate [URLs](urls.md)
- Set up [Automatic Cleanup](cleanup.md)
