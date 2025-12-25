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

### With Validation

```php
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars',
    validate: ['image', 'max:2048', 'mimes:jpg,png']
);
$user->save();
```

### With Metadata

```php
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars',
    metadata: [
        'uploaded_by' => auth()->id(),
        'description' => 'User profile picture',
    ]
);
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
// Attach a file
$user->attachFile('avatar', $request->file('avatar'), folder: 'avatars');
$user->save();

// Clear an attachment
$user->clearAttachments('avatar', deleteFiles: true);
$user->save();
```

## Accessing Attachment Properties

```php
$avatar = $user->avatar;

// Basic properties
$avatar->name();      // File name (e.g., "abc123.jpg")
$avatar->disk();      // Storage disk (e.g., "public")
$avatar->folder();    // Folder path (e.g., "avatars")
$avatar->path();      // Full path (e.g., "avatars/abc123.jpg")
$avatar->size();      // File size in bytes
$avatar->mimeType();  // MIME type (e.g., "image/jpeg")
$avatar->extname();   // File extension (e.g., "jpg")

// URLs
$avatar->url;       // Public URL
$avatar->url();     // Same as above

// Human-readable size
$avatar->readableSize(); // "1.5 MB"

// Metadata
$avatar->metadata('uploaded_by');
$avatar->metadata('description', 'No description');
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

When you replace an attachment, the old file is automatically deleted (if `delete_on_replace` is enabled):

```php
// Old avatar is automatically deleted
$user->avatar = Attachment::fromFile($newFile, folder: 'avatars');
$user->save();
```

## Null Handling

Attachments can be null:

```php
// Check if attachment exists
if ($user->avatar) {
    echo $user->avatar->url;
}

// Set to null
$user->avatar = null;
$user->save();
```

## Next Steps

- Learn about [Multiple Attachments](multiple-attachments.md)
- Configure [File Validation](validation.md)
- Generate [URLs](urls.md)
- Set up [Automatic Cleanup](cleanup.md)
