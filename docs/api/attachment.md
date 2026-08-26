# Attachment API Reference

Complete API reference for the `Attachment` class.

## Creating Attachments

### `fromFile()`

Create an attachment from an uploaded file or an `Illuminate\Http\File`.

```php
public static function fromFile(
    UploadedFile|\Illuminate\Http\File $file,
    ?string $disk = null,
    ?string $folder = null
): static
```

**Parameters:**

- `$file` - The file instance (`UploadedFile` or `Illuminate\Http\File`)
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)

**Returns:** `Attachment` instance

**Throws:**

- `StorageException` - If file storage fails

**Example:**

```php
$attachment = Attachment::fromFile(
    $request->file('avatar'),
    disk: 'public',
    folder: 'avatars'
);
```

### `fromContent()`

Create an attachment from raw bytes, with no temporary file. The filename is optional and only feeds the naming strategy and extension. It is never persisted. When no MIME type is given, it is inferred from the extension.

```php
public static function fromContent(
    string $content,
    ?string $filename = null,
    ?string $disk = null,
    ?string $folder = null,
    ?string $mimeType = null
): static
```

**Parameters:**

- `$content` - The raw file bytes
- `$filename` - Optional filename used for naming and extension only
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)
- `$mimeType` - Optional MIME type (inferred from the extension when omitted)

**Returns:** `Attachment` instance

**Throws:**

- `StorageException` - If file storage fails

**Example:**

```php
$attachment = Attachment::fromContent(
    $pdfBytes,
    filename: 'invoice.pdf',
    folder: 'invoices'
);
```

### `fromStream()`

Create an attachment from a stream resource, written straight to storage without
buffering it in memory the way `fromContent()` does — use this for large sources.
The filename is optional and only feeds the naming strategy and extension. When
no MIME type is given, it is inferred from the extension. The size is read back
from the disk after the write.

```php
public static function fromStream(
    $stream,
    ?string $filename = null,
    ?string $disk = null,
    ?string $folder = null,
    ?string $mimeType = null
): static
```

**Parameters:**

- `$stream` - An open stream **resource** (e.g. from `fopen()`)
- `$filename` - Optional filename used for naming and extension only
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)
- `$mimeType` - Optional MIME type (inferred from the extension when omitted)

**Returns:** `Attachment` instance

**Throws:**

- `StorageException` - If `$stream` is not a resource, or if file storage fails

> The caller owns the stream — `fromStream()` does **not** close it. Close it
> yourself once you are done.

**Example:**

```php
$stream = fopen('/path/to/large-export.csv', 'r');
$attachment = Attachment::fromStream($stream, 'export.csv', folder: 'exports');
fclose($stream);
```

### `fromUrl()`

Create an attachment by streaming a remote URL into the disk. The response body
is streamed straight to storage without buffering the whole payload in memory.
The extension/filename are derived from the URL path and fed to the naming
strategy; the MIME type is taken from the response `Content-Type` when not given.

```php
public static function fromUrl(
    string $url,
    ?string $disk = null,
    ?string $folder = null,
    ?string $mimeType = null
): static
```

**Parameters:**

- `$url` - The URL to fetch
- `$disk` - Storage disk (defaults to config value)
- `$folder` - Folder path (defaults to config value)
- `$mimeType` - Optional MIME type (taken from the response `Content-Type` when omitted)

**Returns:** `Attachment` instance

**Throws:**

- `StorageException` - If the fetch fails or file storage fails

> **Security:** The URL is fetched as-is. Validating or allow-listing the host to
> prevent SSRF is the caller's responsibility.

**Example:**

```php
$attachment = Attachment::fromUrl(
    'https://example.com/avatars/jane.png',
    folder: 'avatars'
);
```

## Accessor Methods

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

### `mime()`

Get MIME type of the file.

```php
public function mime(): ?string
```

**Example:**

```php
echo $attachment->mime(); // "image/jpeg"
```

### `extension()`

Get file extension.

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

Get the file path in storage.

```php
public function path(): ?string
```

**Returns:** Path including folder and filename

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

Get human-readable file size. With no argument the output is trimmed; pass an
integer to render that many fixed decimal places.

```php
public function readableSize(?int $precision = null): string
```

**Parameters:**

- `$precision` - Fixed decimal places (null trims the output)

**Returns:** Formatted size string

**Example:**

```php
echo $attachment->readableSize();  // "2 MB"
echo $attachment->readableSize(2); // "2.00 MB"
```

### `move()`

Move the file to a different disk or folder. A same-disk move is a single
filesystem rename; a cross-disk move copies the bytes over and drops the original.

```php
public function move(?string $disk = null, ?string $folder = null): static
```

**Parameters:**

- `$disk` - Target disk (null to keep current)
- `$folder` - Target folder (null to keep current)

**Returns:** New `Attachment` instance

**Example:**

```php
$newAttachment = $attachment->move('s3', 'archived');
```

### `rename()`

Rename the file, keeping its folder.

```php
public function rename(string $newName, bool $keepExtension = true): static
```

**Parameters:**

- `$newName` - New filename (without extension)
- `$keepExtension` - Whether to keep the original extension

**Returns:** New `Attachment` instance

**Example:**

```php
$renamed = $attachment->rename('profile-picture');
```

### `duplicate()`

Copy the file to a different location. When `$name` is omitted, the copy's name
is generated by the configured naming strategy (just like a fresh upload).

```php
public function duplicate(?string $disk = null, ?string $folder = null, ?string $name = null): static
```

**Parameters:**

- `$disk` - Target disk (null to use current)
- `$folder` - Target folder (null to use current)
- `$name` - New filename (null to generate one via the naming strategy)

**Returns:** New `Attachment` instance

**Example:**

```php
$backup = $attachment->duplicate('s3', 'backups');
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

### `isDocument()`

Check if the file is a document (PDF, Word, Excel, PowerPoint, text, or CSV).

```php
public function isDocument(): bool
```

## Serialization

An attachment serializes to exactly these keys, which is also the JSON shape stored in the database column:

```json
{
  "disk": "public",
  "name": "avatars/abc123.jpg",
  "size": 153600,
  "extname": "jpg",
  "mimeType": "image/jpeg"
}
```

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
