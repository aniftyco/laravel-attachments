# API Resources

Laravel Attachments provides API resources for transforming attachments into JSON responses for your APIs.

## AttachmentResource

Transform a single attachment into a JSON response:

```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

$resource = new AttachmentResource($user->avatar);

return $resource;
```

### Response Format

```json
{
  "data": {
    "url": "https://example.com/storage/avatars/abc123.jpg",
    "mime": "image/jpeg",
    "size": 153600,
    "extension": "jpg",
    "type": "image"
  }
}
```

Each attachment serializes to exactly these five fields: `url`, `mime`, `size`,
`extension`, and `type`. The `type` field is the attachment's category, one of:
`image`, `video`, `audio`, `pdf`, `archive`, `document`, `text`, or `other`.

## Collections of Attachments

Transform multiple attachments with Laravel's standard resource collection:

```php
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

return AttachmentResource::collection($post->images);
```

### Response Format

```json
{
  "data": [
    {
      "url": "https://example.com/storage/posts/image1.jpg",
      "mime": "image/jpeg",
      "size": 204800,
      "extension": "jpg",
      "type": "image"
    },
    {
      "url": "https://example.com/storage/posts/image2.jpg",
      "mime": "image/jpeg",
      "size": 307200,
      "extension": "jpg",
      "type": "image"
    }
  ]
}
```

Need aggregate totals? They come straight from the collection:
`$post->images->totalSize()` and `$post->images->totalReadableSize()`.

## Using in Model Resources

### Single Attachment

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
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
  "email": "john@example.com",
  "avatar": {
    "url": "https://example.com/storage/avatars/abc123.jpg",
    "mime": "image/jpeg",
    "size": 153600,
    "extension": "jpg",
    "type": "image"
  }
}
```

### Multiple Attachments

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'images' => AttachmentResource::collection($this->images),
        ];
    }
}
```

Response:

```json
{
  "id": 1,
  "title": "My Post",
  "content": "Post content...",
  "images": [
    {
      "url": "https://example.com/storage/posts/image1.jpg",
      "mime": "image/jpeg",
      "size": 204800,
      "extension": "jpg",
      "type": "image"
    }
  ]
}
```

## Conditional Inclusion

Include attachments only when they exist:

```php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'avatar' => $this->when(
            $this->avatar,
            new AttachmentResource($this->avatar)
        ),
    ];
}
```

## Custom Resource Fields

### Adding Custom Fields

Extend the resource to add custom fields:

```php
namespace App\Http\Resources;

use NiftyCo\Attachments\Http\Resources\AttachmentResource as BaseResource;

class CustomAttachmentResource extends BaseResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'is_image' => $this->isImage(),
            'download_url' => route('attachments.download', ['path' => $this->path()]),
        ]);
    }
}
```

Usage:

```php
return new CustomAttachmentResource($user->avatar);
```

### Removing Fields

```php
namespace App\Http\Resources;

use NiftyCo\Attachments\Http\Resources\AttachmentResource as BaseResource;

class MinimalAttachmentResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'url' => $this->url(),
            'path' => $this->path(),
        ];
    }
}
```

## Temporary URLs in Resources

For private files, include temporary URLs:

```php
namespace App\Http\Resources;

use NiftyCo\Attachments\Http\Resources\AttachmentResource as BaseResource;

class SecureAttachmentResource extends BaseResource
{
    public function toArray($request): array
    {
        $data = parent::toArray($request);

        // Replace public URL with temporary URL
        if ($this->disk() === 's3-private') {
            $data['url'] = $this->temporaryUrl(now()->addHour());
        }

        return $data;
    }
}
```

## Pagination

Paginate attachment collections:

```php
namespace App\Http\Controllers;

use App\Models\Attachment;
use NiftyCo\Attachments\Http\Resources\AttachmentResource;

class AttachmentController extends Controller
{
    public function index()
    {
        $attachments = Attachment::paginate(15);

        return AttachmentResource::collection($attachments);
    }
}
```

Response:

```json
{
    "data": [...],
    "links": {
        "first": "http://example.com/api/attachments?page=1",
        "last": "http://example.com/api/attachments?page=3",
        "prev": null,
        "next": "http://example.com/api/attachments?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 45
    }
}
```

## Nested Resources

Include attachments in nested resources:

```php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'posts' => PostResource::collection($this->posts),
        ];
    }
}

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'images' => AttachmentResource::collection($this->images),
        ];
    }
}
```

## Best Practices

### 1. Use Conditional Inclusion

```php
'avatar' => $this->when($this->avatar, new AttachmentResource($this->avatar)),
```

### 2. Create Custom Resources for Specific Needs

```php
// For public APIs
class PublicAttachmentResource extends AttachmentResource { }

// For admin APIs
class AdminAttachmentResource extends AttachmentResource { }
```

### 3. Include Only Necessary Data

```php
// Good: Minimal data for list views
public function toArray($request): array
{
    return [
        'url' => $this->url(),
        'path' => $this->path(),
    ];
}

// Bad: Too much data for list views
public function toArray($request): array
{
    return parent::toArray($request); // Includes everything
}
```

### 4. Handle Null Attachments

```php
'avatar' => $this->avatar ? new AttachmentResource($this->avatar) : null,
```

## Next Steps

- Learn about [Filament Integration](filament.md)
- Explore [Testing](testing.md)
- Configure [Events](events.md)
