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
  "url": "https://example.com/storage/avatars/abc123.jpg",
  "name": "abc123.jpg",
  "size": 153600,
  "mime": "image/jpeg",
  "readable_size": "150 KB",
  "metadata": {
    "original_name": "profile.jpg",
    "uploaded_at": "2024-01-15T10:30:00Z"
  }
}
```

## AttachmentCollection

Transform multiple attachments:

```php
use NiftyCo\Attachments\Http\Resources\AttachmentCollection;

$collection = new AttachmentCollection($post->images);

return $collection;
```

### Response Format

```json
{
  "data": [
    {
      "url": "https://example.com/storage/posts/image1.jpg",
      "name": "image1.jpg",
      "size": 204800,
      "mime": "image/jpeg",
      "readable_size": "200 KB"
    },
    {
      "url": "https://example.com/storage/posts/image2.jpg",
      "name": "image2.jpg",
      "size": 307200,
      "mime": "image/jpeg",
      "readable_size": "300 KB"
    }
  ]
}
```

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
    "name": "abc123.jpg",
    "size": 153600,
    "mime": "image/jpeg",
    "readable_size": "150 KB"
  }
}
```

### Multiple Attachments

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use NiftyCo\Attachments\Http\Resources\AttachmentCollection;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'images' => new AttachmentCollection($this->images),
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
  "images": {
    "data": [
      {
        "url": "https://example.com/storage/posts/image1.jpg",
        "name": "image1.jpg",
        "size": 204800,
        "mime": "image/jpeg",
        "readable_size": "200 KB"
      }
    ]
  }
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
            'thumbnail_url' => $this->metadata('thumbnail_url'),
            'is_processed' => $this->metadata('processed', false),
            'uploaded_by' => $this->metadata('uploaded_by'),
        ]);
    }
}
```

Usage:

```php
return new CustomAttachmentResource($user->avatar);
```

Response:

```json
{
  "url": "https://example.com/storage/avatars/abc123.jpg",
  "name": "abc123.jpg",
  "size": 153600,
  "mime": "image/jpeg",
  "readable_size": "150 KB",
  "thumbnail_url": "https://example.com/storage/thumbnails/abc123.jpg",
  "is_processed": true,
  "uploaded_by": 1
}
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
            'name' => $this->name(),
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
        if ($this->disk === 's3-private') {
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
use NiftyCo\Attachments\Http\Resources\AttachmentCollection;

class AttachmentController extends Controller
{
    public function index()
    {
        $attachments = Attachment::paginate(15);

        return new AttachmentCollection($attachments);
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
            'images' => new AttachmentCollection($this->images),
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
        'name' => $this->name(),
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
