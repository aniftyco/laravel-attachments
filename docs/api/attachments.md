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
    ?string $folder = null
): static
```

**Parameters:**

- `$files` - Array of `UploadedFile` instances
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)

**Returns:** `Attachments` collection instance

**Throws:**

- `StorageException` - If file storage fails

**Example:**

```php
$attachments = Attachments::fromFiles(
    $request->file('images'),
    disk: 'public',
    folder: 'posts'
);
```

### `fromContents()`

Create a collection from multiple raw contents. Each item needs a `content` key and may include optional `filename` and `mimeType` keys. As with `Attachment::fromContent()`, the filename only feeds the naming strategy and extension and is never persisted.

```php
public static function fromContents(
    array $items,
    ?string $disk = null,
    ?string $folder = null
): static
```

**Parameters:**

- `$items` - Array of `['content' => string, 'filename' => ?string, 'mimeType' => ?string]` items
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)

**Returns:** `Attachments` collection instance

**Throws:**

- `StorageException` - If an item is missing its `content` key, or if file storage fails

**Example:**

```php
$attachments = Attachments::fromContents([
    ['content' => $firstBytes, 'filename' => 'chart.png'],
    ['content' => $secondBytes, 'mimeType' => 'image/jpeg'],
], folder: 'posts');
```

### `fromUrls()`

Create a collection by streaming multiple remote URLs into the disk. See
`Attachment::fromUrl()`.

```php
public static function fromUrls(
    array $urls,
    ?string $disk = null,
    ?string $folder = null
): static
```

**Parameters:**

- `$urls` - Array of URL strings
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)

**Returns:** `Attachments` collection instance

**Throws:**

- `StorageException` - If any fetch or file storage fails

> **Security:** These URLs are fetched as-is. Validating or allow-listing the
> hosts to prevent SSRF is the caller's responsibility.

**Example:**

```php
$attachments = Attachments::fromUrls([
    'https://example.com/a.png',
    'https://example.com/b.png',
], folder: 'posts');
```

## Adding Attachments

### `attach()`

Add a file to the collection.

```php
public function attach(
    UploadedFile $file,
    ?string $disk = null,
    ?string $folder = null
): static
```

**Parameters:**

- `$file` - The uploaded file instance
- `$disk` - Storage disk
- `$folder` - Folder path

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

### `duplicate()`

Duplicate all attachments to a different location. Each copy's name is generated
by the configured naming strategy.

```php
public function duplicate(?string $disk = null, ?string $folder = null): static
```

**Parameters:**

- `$disk` - Target disk (null to keep each attachment's disk)
- `$folder` - Target folder

**Returns:** New `Attachments` collection with duplicated files

**Example:**

```php
$backups = $post->images->duplicate('backup', 'backups/posts');
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

Get human-readable total size. With no argument the output is trimmed; pass an
integer to render that many fixed decimal places.

```php
public function totalReadableSize(?int $precision = null): string
```

**Parameters:**

- `$precision` - Fixed decimal places (null trims the output)

**Returns:** Formatted size string

**Example:**

```php
echo $post->images->totalReadableSize();  // "15.3 MB"
echo $post->images->totalReadableSize(2); // "15.30 MB"
```

## Filtering Methods

### `ofType()`

Filter attachments by category, using each attachment's `type()`.

```php
public function ofType(string $type): static
```

**Parameters:**

- `$type` - One of: `'image'`, `'video'`, `'audio'`, `'pdf'`, `'archive'`, `'document'`, `'text'`, `'other'`

**Returns:** Filtered collection

Note: `pdf` and `archive` are their own categories, so `ofType('document')`
returns only true documents (Office/OpenDocument/RTF/text/CSV), not PDFs or archives.

**Example:**

```php
$images = $attachments->ofType('image');
$pdfs = $attachments->ofType('pdf');
$archives = $attachments->ofType('archive');
```

## Standard Collection Methods

Since `Attachments` extends Laravel's `Collection`, all standard collection methods are available:

### Iteration

```php
// Loop through attachments
foreach ($post->images as $image) {
    echo $image->url();
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

// Filter by MIME type
$jpegs = $post->images->filter(fn($img) => $img->mime() === 'image/jpeg');
```

### Mapping

```php
// Map to URLs
$urls = $post->images->map(fn($img) => $img->url());

// Map to paths
$paths = $post->images->map(fn($img) => $img->path());
```

### Sorting

```php
// Sort by size
$sorted = $post->images->sortBy(fn($img) => $img->size());

// Sort by path
$sorted = $post->images->sortBy(fn($img) => $img->path());

// Sort descending by size
$sorted = $post->images->sortByDesc(fn($img) => $img->size());
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
$specific = $post->images->first(fn($img) => $img->path() === 'posts/photo.jpg');
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
$hasPhoto = $post->images->contains(fn($img) => $img->path() === 'posts/photo.jpg');

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
    ->map(fn($img) => $img->url())
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
    'average_size' => $post->images->avg(fn($img) => $img->size()),
    'largest' => $post->images->max(fn($img) => $img->size()),
    'smallest' => $post->images->min(fn($img) => $img->size()),
];
```

## Next Steps

- [Attachment API](attachment.md)
- [Configuration Reference](configuration.md)
