# Metadata

Metadata allows you to store additional information about your attachments beyond the basic file properties.

## What is Metadata?

Metadata is custom data stored alongside your attachment information. It can include:
- Original filename
- Upload timestamp
- User who uploaded the file
- File description
- Custom tags or categories
- Processing status
- Any other custom data

## Enabling Metadata

Metadata is enabled by default. Configure it in your config file:

```php
// config/attachments.php
return [
    'metadata' => [
        'enabled' => true,
        
        'auto_capture' => [
            'original_name' => true,
            'uploaded_at' => true,
            'uploaded_by' => false, // Requires authentication
        ],
    ],
];
```

## Adding Metadata

### During Upload

```php
use NiftyCo\Attachments\Attachment;

$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars',
    metadata: [
        'uploaded_by' => auth()->id(),
        'description' => 'User profile picture',
        'category' => 'avatar',
        'processed' => false,
    ]
);
$user->save();
```

### After Upload

```php
$attachment = $user->avatar;

// Set metadata
$attachment->setMetadata('description', 'Updated description');
$attachment->setMetadata('processed', true);

// Set multiple metadata values
$attachment->setMetadata([
    'description' => 'Updated description',
    'processed' => true,
    'processed_at' => now()->toIso8601String(),
]);
```

## Accessing Metadata

### Get Single Value

```php
$description = $user->avatar->metadata('description');

// With default value
$category = $user->avatar->metadata('category', 'uncategorized');
```

### Get All Metadata

```php
$allMetadata = $user->avatar->metadata();

// Returns array:
// [
//     'original_name' => 'profile.jpg',
//     'uploaded_at' => '2024-01-15T10:30:00Z',
//     'uploaded_by' => 1,
//     'description' => 'User profile picture',
// ]
```

### Check if Metadata Exists

```php
if ($user->avatar->hasMetadata('description')) {
    echo $user->avatar->metadata('description');
}
```

## Auto-Captured Metadata

### Original Filename

Automatically stores the original filename:

```php
// User uploads "my-photo.jpg"
$user->avatar = Attachment::fromFile($request->file('avatar'), folder: 'avatars');

// File is stored as "abc123.jpg" but original name is preserved
echo $user->avatar->metadata('original_name'); // "my-photo.jpg"
```

### Upload Timestamp

Automatically stores when the file was uploaded:

```php
echo $user->avatar->metadata('uploaded_at'); // "2024-01-15T10:30:00Z"

// Parse as Carbon instance
$uploadedAt = Carbon::parse($user->avatar->metadata('uploaded_at'));
echo $uploadedAt->diffForHumans(); // "2 hours ago"
```

### Uploaded By User

Automatically stores the authenticated user's ID:

```php
// Enable in config
'auto_capture' => [
    'uploaded_by' => true,
],

// Access the user ID
$userId = $user->avatar->metadata('uploaded_by');

// Get the user
$uploader = User::find($user->avatar->metadata('uploaded_by'));
```

## Common Use Cases

### File Descriptions

```php
$document->setMetadata('description', 'Q4 Financial Report');
$document->setMetadata('title', 'Financial Report - Q4 2024');
```

### Categorization

```php
$attachment->setMetadata('category', 'invoice');
$attachment->setMetadata('tags', ['important', 'tax', '2024']);
```

### Processing Status

```php
// Mark as pending processing
$image->setMetadata('processed', false);
$image->setMetadata('processing_status', 'pending');

// After processing
$image->setMetadata('processed', true);
$image->setMetadata('processing_status', 'completed');
$image->setMetadata('processed_at', now()->toIso8601String());
```

### Image Dimensions

```php
$image = Attachment::fromFile($file, folder: 'images');

// Store dimensions after upload
[$width, $height] = getimagesize($file->getRealPath());
$image->setMetadata([
    'width' => $width,
    'height' => $height,
    'aspect_ratio' => $width / $height,
]);
```

### File Relationships

```php
// Link to related models
$attachment->setMetadata('related_to', [
    'type' => 'invoice',
    'id' => $invoice->id,
]);

// Store version information
$attachment->setMetadata('version', '2.0');
$attachment->setMetadata('replaces', $oldAttachment->name);
```

## Metadata in Collections

### Filter by Metadata

```php
// Get all processed images
$processed = $post->images->filter(function ($image) {
    return $image->metadata('processed') === true;
});

// Get images by category
$avatars = $attachments->filter(function ($attachment) {
    return $attachment->metadata('category') === 'avatar';
});
```

### Map Metadata

```php
// Get all descriptions
$descriptions = $post->images->map(function ($image) {
    return $image->metadata('description', 'No description');
});

// Get upload dates
$uploadDates = $post->images->map(function ($image) {
    return $image->metadata('uploaded_at');
});
```

## Metadata in API Resources

```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'attachment' => new AttachmentResource($this->attachment),
        ];
    }
}
```

Response includes metadata:

```json
{
    "id": 1,
    "title": "Financial Report",
    "attachment": {
        "url": "https://example.com/storage/docs/abc123.pdf",
        "name": "abc123.pdf",
        "size": 1048576,
        "mime": "application/pdf",
        "metadata": {
            "original_name": "Q4-Report.pdf",
            "uploaded_at": "2024-01-15T10:30:00Z",
            "description": "Q4 Financial Report",
            "category": "financial"
        }
    }
}
```

## Searching by Metadata

```php
// Find attachments by metadata
$invoices = Attachment::whereJsonContains('metadata->category', 'invoice')->get();

// Find by uploaded user
$myUploads = Attachment::whereJsonContains('metadata->uploaded_by', auth()->id())->get();
```

**Note:** This requires querying the database directly, not through the cast.

## Best Practices

### 1. Use Consistent Keys

```php
// Good: Consistent naming
$attachment->setMetadata('uploaded_by', auth()->id());
$attachment->setMetadata('uploaded_at', now()->toIso8601String());

// Bad: Inconsistent naming
$attachment->setMetadata('uploader', auth()->id());
$attachment->setMetadata('upload_time', now()->toIso8601String());
```

### 2. Store Serializable Data

```php
// Good: Simple types
$attachment->setMetadata('tags', ['important', 'urgent']);
$attachment->setMetadata('count', 5);

// Bad: Complex objects
$attachment->setMetadata('user', $user); // Don't store models
```

### 3. Use ISO 8601 for Dates

```php
// Good: ISO 8601 format
$attachment->setMetadata('uploaded_at', now()->toIso8601String());

// Bad: Inconsistent format
$attachment->setMetadata('uploaded_at', now()->format('Y-m-d H:i:s'));
```

### 4. Document Your Metadata Schema

```php
/**
 * Metadata schema:
 * - original_name: string - Original filename
 * - uploaded_by: int - User ID who uploaded
 * - category: string - File category (avatar, document, etc.)
 * - processed: bool - Whether file has been processed
 * - tags: array - Array of tag strings
 */
```

## Next Steps

- Learn about [Events](events.md)
- Explore [API Resources](api-resources.md)
- Configure [Storage](storage.md)

