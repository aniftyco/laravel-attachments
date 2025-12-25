# Attachment API Reference

Complete API reference for the `Attachment` class.

## Creating Attachments

### `fromFile()`

Create an attachment from an uploaded file.

```php
public static function fromFile(
    UploadedFile $file,
    ?string $disk = null,
    ?string $folder = null,
    array|string|null $validate = null,
    array $metadata = []
): static
```

**Parameters:**

- `$file` - The uploaded file instance
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)
- `$validate` - Validation rules (array or string)
- `$metadata` - Additional metadata to store

**Returns:** `Attachment` instance

**Throws:**

- `ValidationException` - If validation fails
- `StorageException` - If file storage fails

**Example:**

```php
$attachment = Attachment::fromFile(
    $request->file('avatar'),
    disk: 'public',
    folder: 'avatars',
    validate: ['image', 'max:2048'],
    metadata: ['uploaded_by' => auth()->id()]
);
```

## Accessor Methods

### `name()`

Get the file name in storage.

```php
public function name(): ?string
```

**Example:**

```php
echo $attachment->name(); // "abc123.jpg"
```

### `disk()`

Get the storage disk name.

```php
public function disk(): ?string
```

**Example:**

```php
echo $attachment->disk(); // "public"
```

### `size()`

Get file size in bytes.

```php
public function size(): ?int
```

**Example:**

```php
echo $attachment->size(); // 153600
```

### `mimeType()`

Get MIME type of the file.

```php
public function mimeType(): ?string
```

**Example:**

```php
echo $attachment->mimeType(); // "image/jpeg"
```

### `extname()`

Get file extension.

```php
public function extname(): ?string
```

**Example:**

```php
echo $attachment->extname(); // "jpg"
```

### `extension()`

Get file extension (alias for `extname()`).

```php
public function extension(): ?string
```

**Example:**

```php
echo $attachment->extension(); // "jpg"
```

### `folder()`

Get the folder/directory path.

```php
public function folder(): ?string
```

**Example:**

```php
echo $attachment->folder(); // "avatars"
```

## Methods

### `path()`

Get the full storage path.

```php
public function path(): string
```

**Returns:** Full path including folder and name

**Example:**

```php
echo $attachment->path(); // "avatars/abc123.jpg"
```

### `url()`

Get the public URL.

```php
public function url(): string
```

**Returns:** Public URL to the file

**Example:**

```php
echo $attachment->url(); // "https://example.com/storage/avatars/abc123.jpg"
```

### `temporaryUrl()`

Generate a temporary URL (for private disks).

```php
public function temporaryUrl(DateTimeInterface|int $expiration): string
```

**Parameters:**

- `$expiration` - Expiration time (Carbon instance or minutes)

**Returns:** Temporary signed URL

**Example:**

```php
$url = $attachment->temporaryUrl(now()->addHour());
$url = $attachment->temporaryUrl(60); // 60 minutes
```

### `exists()`

Check if the file exists in storage.

```php
public function exists(): bool
```

**Returns:** `true` if file exists, `false` otherwise

**Example:**

```php
if ($attachment->exists()) {
    // File exists
}
```

### `contents()`

Get the file contents.

```php
public function contents(): string
```

**Returns:** File contents as string

**Example:**

```php
$contents = $attachment->contents();
```

### `download()`

Create a download response.

```php
public function download(?string $name = null): StreamedResponse
```

**Parameters:**

- `$name` - Optional custom filename for download

**Returns:** Laravel download response

**Example:**

```php
return $attachment->download();
return $attachment->download('custom-name.jpg');
```

### `delete()`

Delete the file from storage.

```php
public function delete(): bool
```

**Returns:** `true` if deleted successfully

**Example:**

```php
$attachment->delete();
```

### `readableSize()`

Get human-readable file size.

```php
public function readableSize(): string
```

**Returns:** Formatted size string

**Example:**

```php
echo $attachment->readableSize(); // "1.5 MB"
```

### `move()`

Move the file to a different disk or folder.

```php
public function move(?string $disk = null, ?string $folder = null, ?string $name = null): static
```

**Parameters:**

- `$disk` - Target disk (null to keep current)
- `$folder` - Target folder (null to keep current)
- `$name` - New filename (null to keep current)

**Returns:** New `Attachment` instance

**Example:**

```php
$newAttachment = $attachment->move('s3', 'archived');
```

### `copy()`

Copy the file to a different location.

```php
public function copy(?string $disk = null, ?string $folder = null, ?string $name = null): static
```

**Parameters:**

- `$disk` - Target disk (null to use current)
- `$folder` - Target folder (null to use current)
- `$name` - New filename (null to auto-generate)

**Returns:** New `Attachment` instance

**Example:**

```php
$backup = $attachment->copy('s3', 'backups');
```

## Type Checking Methods

### `isImage()`

Check if the file is an image.

```php
public function isImage(): bool
```

**Example:**

```php
if ($attachment->isImage()) {
    // It's an image
}
```

### `isPdf()`

Check if the file is a PDF.

```php
public function isPdf(): bool
```

### `isVideo()`

Check if the file is a video.

```php
public function isVideo(): bool
```

### `isAudio()`

Check if the file is an audio file.

```php
public function isAudio(): bool
```

## Metadata Methods

### `metadata()`

Get metadata value(s).

```php
public function metadata(?string $key = null, mixed $default = null): mixed
```

**Parameters:**

- `$key` - Metadata key (null to get all)
- `$default` - Default value if key doesn't exist

**Returns:** Metadata value or array of all metadata

**Example:**

```php
$value = $attachment->metadata('uploaded_by');
$all = $attachment->metadata();
```

### `setMetadata()`

Set metadata value(s).

```php
public function setMetadata(string|array $key, mixed $value = null): static
```

**Parameters:**

- `$key` - Metadata key or array of key-value pairs
- `$value` - Value (when $key is string)

**Returns:** `$this` for chaining

**Example:**

```php
$attachment->setMetadata('processed', true);
$attachment->setMetadata(['processed' => true, 'processed_at' => now()]);
```

### `hasMetadata()`

Check if metadata key exists.

```php
public function hasMetadata(string $key): bool
```

**Example:**

```php
if ($attachment->hasMetadata('description')) {
    // Metadata exists
}
```

## Serialization

### `toArray()`

Convert to array.

```php
public function toArray(): array
```

### `toJson()`

Convert to JSON string.

```php
public function toJson(int $options = 0): string
```

## Next Steps

- [Attachments Collection API](attachments.md)
- [Configuration Reference](configuration.md)
