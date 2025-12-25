# Attachments Collection API Reference

Complete API reference for the `Attachments` collection class.

## Overview

The `Attachments` class extends Laravel's `Collection`, providing all standard collection methods plus attachment-specific functionality.

```php
use NiftyCo\Attachments\Attachments;
```

## Creating Collections

### `fromFiles()`

Create a collection from multiple uploaded files.

```php
public static function fromFiles(
    array $files,
    ?string $disk = null,
    ?string $folder = null,
    array|string|null $validate = null
): static
```

**Parameters:**

- `$files` - Array of `UploadedFile` instances
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)
- `$validate` - Validation rules applied to each file

**Returns:** `Attachments` collection instance

**Throws:**

- `ValidationException` - If any file fails validation
- `StorageException` - If file storage fails

**Example:**

```php
$attachments = Attachments::fromFiles(
    $request->file('images'),
    disk: 'public',
    folder: 'posts',
    validate: ['image', 'max:5120']
);
```

## Adding Attachments

### `attach()`

Add a file to the collection.

```php
public function attach(
    UploadedFile $file,
    ?string $disk = null,
    ?string $folder = null,
    array|string|null $validate = null
): static
```

**Parameters:**

- `$file` - The uploaded file instance
- `$disk` - Storage disk
- `$folder` - Folder path
- `$validate` - Validation rules

**Returns:** `$this` for chaining

**Example:**

```php
$attachments = new Attachments();
$attachments->attach($file1, folder: 'posts');
$attachments->attach($file2, folder: 'posts');
```

## Collection Operations

### `delete()`

Delete all files in the collection from storage.

```php
public function delete(): bool
```

**Returns:** `true` if all files deleted successfully

**Example:**

```php
$post->images->delete();
```

### `move()`

Move all attachments to a different disk or folder.

```php
public function move(?string $disk = null, ?string $folder = null): static
```

**Parameters:**

- `$disk` - Target disk (null to keep current)
- `$folder` - Target folder (null to keep current)

**Returns:** New `Attachments` collection with moved files

**Example:**

```php
$movedImages = $post->images->move('s3', 'archived-posts');
```

### `copy()`

Copy all attachments to a different location.

```php
public function copy(?string $disk = null, ?string $folder = null): static
```

**Parameters:**

- `$disk` - Target disk
- `$folder` - Target folder

**Returns:** New `Attachments` collection with copied files

**Example:**

```php
$backups = $post->images->copy('backup', 'backups/posts');
```

### `archive()`

Create a ZIP archive of all attachments.

```php
public function archive(string $archiveName, ?string $disk = null, ?string $folder = null): Attachment
```

**Parameters:**

- `$archiveName` - Name of the ZIP file
- `$disk` - Disk to store the archive (defaults to first attachment's disk)
- `$folder` - Folder to store the archive (defaults to 'archives')

**Returns:** `Attachment` instance of the created archive

**Example:**

```php
$archive = $post->images->archive('post-images.zip');
echo $archive->url(); // URL to the archive file
```

## Size Methods

### `totalSize()`

Get the total size of all attachments in bytes.

```php
public function totalSize(): int
```

**Returns:** Total size in bytes

**Example:**

```php
$bytes = $post->images->totalSize();
```

### `totalReadableSize()`

Get human-readable total size.

```php
public function totalReadableSize(): string
```

**Returns:** Formatted size string

**Example:**

```php
echo $post->images->totalReadableSize(); // "15.3 MB"
```

## Filtering Methods

### `ofType()`

Filter attachments by type.

```php
public function ofType(string $type): static
```

**Parameters:**

- `$type` - Type to filter by: `'image'`, `'pdf'`, `'video'`, `'audio'`

**Returns:** Filtered collection

**Example:**

```php
$images = $attachments->ofType('image');
$pdfs = $attachments->ofType('pdf');
$videos = $attachments->ofType('video');
```

## Standard Collection Methods

Since `Attachments` extends Laravel's `Collection`, all standard collection methods are available:

### Iteration

```php
// Loop through attachments
foreach ($post->images as $image) {
    echo $image->url;
}

// Each
$post->images->each(function ($image) {
    // Process each image
});
```

### Filtering

```php
// Filter
$large = $post->images->filter(fn($img) => $img->size() > 1048576);

// Reject
$small = $post->images->reject(fn($img) => $img->size() > 1048576);

// Where
$jpegs = $post->images->where('mime', 'image/jpeg');
```

### Mapping

```php
// Map
$urls = $post->images->map(fn($img) => $img->url);

// Pluck
$names = $post->images->pluck('name');
```

### Sorting

```php
// Sort by size
$sorted = $post->images->sortBy('size');

// Sort by name
$sorted = $post->images->sortBy('name');

// Sort descending
$sorted = $post->images->sortByDesc('size');
```

### Counting

```php
// Count
$count = $post->images->count();

// Is empty
if ($post->images->isEmpty()) {
    // No images
}

// Is not empty
if ($post->images->isNotEmpty()) {
    // Has images
}
```

### Accessing Items

```php
// First
$first = $post->images->first();

// Last
$last = $post->images->last();

// Get by index
$second = $post->images->get(1);

// Find
$specific = $post->images->first(fn($img) => $img->name() === 'photo.jpg');
```

### Slicing

```php
// Take first 3
$first3 = $post->images->take(3);

// Skip first 2
$remaining = $post->images->skip(2);

// Slice
$middle = $post->images->slice(2, 5);
```

### Chunking

```php
// Chunk into groups of 10
$post->images->chunk(10)->each(function ($chunk) {
    // Process chunk
});
```

### Checking

```php
// Contains
$hasPhoto = $post->images->contains(fn($img) => $img->name() === 'photo.jpg');

// Every
$allImages = $post->images->every(fn($img) => $img->isImage());

// Some
$hasLarge = $post->images->some(fn($img) => $img->size() > 1048576);
```

### Transforming

```php
// To array
$array = $post->images->toArray();

// To JSON
$json = $post->images->toJson();

// Values
$values = $post->images->values();

// Keys
$keys = $post->images->keys();
```

## Examples

### Filter and Process

```php
// Get all images larger than 1MB
$largeImages = $post->images
    ->filter(fn($img) => $img->isImage())
    ->filter(fn($img) => $img->size() > 1048576);

// Process them
$largeImages->each(function ($image) {
    // Optimize or resize
});
```

### Get URLs

```php
// Get all image URLs
$urls = $post->images
    ->filter(fn($img) => $img->isImage())
    ->map(fn($img) => $img->url)
    ->values();
```

### Group by Type

```php
$grouped = $post->attachments->groupBy(function ($attachment) {
    if ($attachment->isImage()) return 'images';
    if ($attachment->isPdf()) return 'pdfs';
    return 'other';
});

$images = $grouped->get('images');
$pdfs = $grouped->get('pdfs');
```

### Calculate Statistics

```php
$stats = [
    'total' => $post->images->count(),
    'total_size' => $post->images->totalSize(),
    'average_size' => $post->images->avg('size'),
    'largest' => $post->images->max('size'),
    'smallest' => $post->images->min('size'),
];
```

## Next Steps

- [Attachment API](attachment.md)
- [Configuration Reference](configuration.md)
