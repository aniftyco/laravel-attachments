# Multiple Attachments

Multiple attachments allow you to handle collections of files on a single model attribute, perfect for photo galleries, document collections, or any scenario requiring multiple files.

## Setup

### Migration

Create an attachments column in your migration:

```php
use Illuminate\Database\Schema\Blueprint;

Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->attachments('images');
    $table->timestamps();
});
```

The `attachments()` macro creates a nullable JSON column for storing multiple attachments.

### Model

Add the `AsAttachments` cast to your model:

```php
use Illuminate\Database\Eloquent\Model;
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

## Creating Attachments

### From Multiple Files

```php
use NiftyCo\Attachments\Attachments;

// Create from all files at once
$post->images = Attachments::fromFiles(
    $request->file('images'),
    folder: 'posts'
);
$post->save();
```

### Adding Files One at a Time

```php
$post->images = new Attachments();

foreach ($request->file('images') as $image) {
    $post->images->attach($image, folder: 'posts');
}

$post->save();
```

### Adding to Existing Collection

```php
// Add a new file to existing attachments
$post->images->attach($request->file('new_image'), folder: 'posts');
$post->save();
```

### With Validation

```php
$post->images = Attachments::fromFiles(
    $request->file('images'),
    folder: 'posts',
    validate: ['image', 'max:5120', 'mimes:jpg,png']
);
$post->save();
```

## Using the HasAttachments Trait

```php
use NiftyCo\Attachments\Concerns\HasAttachments;

class Post extends Model
{
    use HasAttachments;

    protected function casts(): array
    {
        return [
            'images' => AsAttachments::class,
        ];
    }
}
```

Helper methods:

```php
// Attach multiple files
$post->attachFiles('images', $request->file('images'), folder: 'posts');
$post->save();

// Add a single file to collection
$post->addAttachment('images', $request->file('image'), folder: 'posts');
$post->save();

// Remove an attachment by name
$post->removeAttachment('images', 'photo.jpg');
$post->save();

// Clear all attachments
$post->clearAttachments('images', deleteFiles: true);
$post->save();
```

## Working with Collections

The `Attachments` class extends Laravel's `Collection`, so you have access to all collection methods.

### Counting Attachments

```php
$count = $post->images->count();
```

### Iterating Over Attachments

```php
foreach ($post->images as $image) {
    echo $image->url();
    echo $image->readableSize();
}
```

### Filtering Attachments

```php
// Get only images
$images = $post->images->filter(fn($file) => $file->isImage());

// Get files larger than 1MB
$large = $post->images->filter(fn($file) => $file->size() > 1048576);

// Get PDFs
$pdfs = $post->images->filter(fn($file) => $file->isPdf());
```

### Mapping Attachments

```php
// Get all URLs
$urls = $post->images->map(fn($image) => $image->url());

// Get all file names
$names = $post->images->map(fn($image) => $image->name());
```

### Sorting Attachments

```php
// Sort by size
$sorted = $post->images->sortBy(fn($img) => $img->size());

// Sort by name
$sorted = $post->images->sortBy(fn($img) => $img->name());
```

## Collection Operations

### Total Size

Get the total size of all attachments:

```php
$totalBytes = $post->images->totalSize();
$totalReadable = $post->images->totalReadableSize(); // "15.3 MB"
```

### Filter by Type

```php
// Get only images
$images = $post->images->ofType('image');

// Get only PDFs
$pdfs = $post->images->ofType('pdf');

// Get only videos
$videos = $post->images->ofType('video');
```

### Delete All Attachments

```php
// Delete all files from storage
$post->images->delete();

// Then clear the attribute
$post->images = new Attachments();
$post->save();
```

### Move All Attachments

```php
// Move all attachments to a different disk/folder
$post->images->move('s3', 'archived-posts');
$post->save();
```

### Copy All Attachments

```php
// Copy all attachments to a backup location
$post->images->copy('backup', 'backups/posts');
```

### Archive Attachments

```php
// Create a ZIP archive of all attachments
$post->images->archive('post-images.zip');
```

## Removing Attachments

### Remove by Name

```php
$post->images = $post->images->filter(fn($img) => $img->name() !== 'old.jpg');
$post->save();
```

### Remove by Index

```php
$post->images = $post->images->forget(0); // Remove first attachment
$post->save();
```

### Remove Multiple

```php
$namesToRemove = ['photo1.jpg', 'photo2.jpg'];
$post->images = $post->images->filter(
    fn($img) => !in_array($img->name(), $namesToRemove)
);
$post->save();
```

## Accessing Individual Attachments

```php
// Get first attachment
$first = $post->images->first();

// Get last attachment
$last = $post->images->last();

// Get by index
$second = $post->images->get(1);

// Find by name
$specific = $post->images->first(fn($img) => $img->name() === 'photo.jpg');
```

## Checking for Attachments

```php
// Check if collection is empty
if ($post->images->isEmpty()) {
    // No attachments
}

// Check if collection has items
if ($post->images->isNotEmpty()) {
    // Has attachments
}

// Check if specific file exists
$hasPhoto = $post->images->contains(fn($img) => $img->name() === 'photo.jpg');
```

## Next Steps

- Learn about [File Validation](validation.md)
- Configure [Automatic Cleanup](cleanup.md)
- Generate [URLs](urls.md)
- Use [API Resources](api-resources.md)
