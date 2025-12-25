# Testing

Laravel Attachments provides testing helpers to make it easy to test file uploads and attachments in your application.

## Testing Trait

Use the `InteractsWithAttachments` trait in your tests:

```php
namespace Tests\Feature;

use Tests\TestCase;
use NiftyCo\Attachments\Testing\InteractsWithAttachments;

class UserAvatarTest extends TestCase
{
    use InteractsWithAttachments;
    
    // Your tests...
}
```

## Creating Fake Attachments

### Fake Image

```php
public function test_user_can_upload_avatar()
{
    $attachment = $this->createFakeAttachment('image');
    
    $this->assertInstanceOf(Attachment::class, $attachment);
    $this->assertTrue($attachment->isImage());
}
```

### Fake PDF

```php
public function test_user_can_upload_document()
{
    $attachment = $this->createFakeAttachment('pdf');
    
    $this->assertTrue($attachment->isPdf());
}
```

### Custom Fake File

```php
public function test_user_can_upload_custom_file()
{
    $attachment = $this->createFakeAttachment(
        type: 'image',
        name: 'test-avatar.jpg',
        sizeInKb: 500
    );
    
    $this->assertEquals('test-avatar.jpg', $attachment->metadata('original_name'));
}
```

## Assertions

### Assert Attachment Exists

```php
public function test_attachment_exists_in_storage()
{
    $user = User::factory()->create();
    $user->avatar = $this->createFakeAttachment('image');
    $user->save();
    
    $this->assertAttachmentExists($user->avatar);
}
```

### Assert Attachment Missing

```php
public function test_attachment_deleted()
{
    $user = User::factory()->create();
    $user->avatar = $this->createFakeAttachment('image');
    $user->save();
    
    $attachment = $user->avatar;
    $attachment->delete();
    
    $this->assertAttachmentMissing($attachment);
}
```

### Assert Attachment Disk

```php
public function test_attachment_stored_on_correct_disk()
{
    $attachment = $this->createFakeAttachment('image', disk: 's3');
    
    $this->assertAttachmentDisk($attachment, 's3');
}
```

### Assert Attachment Size

```php
public function test_attachment_has_correct_size()
{
    $attachment = $this->createFakeAttachment('image', sizeInKb: 500);
    
    $this->assertAttachmentSize($attachment, 500 * 1024); // bytes
}
```

## Testing File Uploads

### Basic Upload Test

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

public function test_user_can_upload_avatar()
{
    Storage::fake('public');
    
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);
    
    $response = $this->actingAs($user)
        ->post('/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertSuccessful();
    
    $user->refresh();
    $this->assertNotNull($user->avatar);
    $this->assertAttachmentExists($user->avatar);
}
```

### Multiple Files Upload Test

```php
public function test_user_can_upload_multiple_images()
{
    Storage::fake('public');
    
    $post = Post::factory()->create();
    $files = [
        UploadedFile::fake()->image('image1.jpg'),
        UploadedFile::fake()->image('image2.jpg'),
        UploadedFile::fake()->image('image3.jpg'),
    ];
    
    $response = $this->post("/posts/{$post->id}/images", [
        'images' => $files,
    ]);
    
    $response->assertSuccessful();
    
    $post->refresh();
    $this->assertCount(3, $post->images);
}
```

### Validation Test

```php
public function test_avatar_must_be_image()
{
    Storage::fake('public');
    
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100);
    
    $response = $this->actingAs($user)
        ->post('/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertSessionHasErrors('avatar');
}

public function test_avatar_must_not_exceed_size_limit()
{
    Storage::fake('public');
    
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB
    
    $response = $this->actingAs($user)
        ->post('/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertSessionHasErrors('avatar');
}
```

## Testing Cleanup

### Test Model Deletion Cleanup

```php
use NiftyCo\Attachments\Concerns\HasAttachmentCleanup;

public function test_avatar_deleted_when_user_deleted()
{
    Storage::fake('public');
    
    $user = User::factory()->create();
    $user->avatar = $this->createFakeAttachment('image');
    $user->save();
    
    $attachment = $user->avatar;
    
    $this->assertAttachmentExists($attachment);
    
    $user->delete();
    
    $this->assertAttachmentMissing($attachment);
}
```

### Test Replacement Cleanup

```php
public function test_old_avatar_deleted_when_replaced()
{
    Storage::fake('public');
    
    $user = User::factory()->create();
    $oldAvatar = $this->createFakeAttachment('image');
    $user->avatar = $oldAvatar;
    $user->save();
    
    $newAvatar = $this->createFakeAttachment('image');
    $user->avatar = $newAvatar;
    $user->save();
    
    $this->assertAttachmentMissing($oldAvatar);
    $this->assertAttachmentExists($newAvatar);
}
```

## Testing Events

```php
use Illuminate\Support\Facades\Event;
use NiftyCo\Attachments\Events\AttachmentCreated;

public function test_attachment_created_event_dispatched()
{
    Event::fake([AttachmentCreated::class]);
    
    $user = User::factory()->create();
    $user->avatar = $this->createFakeAttachment('image');
    $user->save();
    
    Event::assertDispatched(AttachmentCreated::class);
}
```

## Factory Integration

Create a factory with attachments:

```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NiftyCo\Attachments\Attachment;
use Illuminate\Http\UploadedFile;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
    
    public function withAvatar(): static
    {
        return $this->afterCreating(function (User $user) {
            $file = UploadedFile::fake()->image('avatar.jpg');
            $user->avatar = Attachment::fromFile($file, folder: 'avatars');
            $user->save();
        });
    }
}
```

Usage:

```php
$user = User::factory()->withAvatar()->create();
```

## Best Practices

### 1. Always Fake Storage

```php
Storage::fake('public');
```

### 2. Use Assertions

```php
$this->assertAttachmentExists($attachment);
$this->assertAttachmentMissing($attachment);
```

### 3. Test Validation

```php
public function test_validates_file_type() { }
public function test_validates_file_size() { }
public function test_validates_image_dimensions() { }
```

### 4. Test Cleanup

```php
public function test_cleanup_on_delete() { }
public function test_cleanup_on_replace() { }
```

### 5. Test Edge Cases

```php
public function test_handles_null_attachment() { }
public function test_handles_missing_file() { }
public function test_handles_invalid_disk() { }
```

## Complete Test Example

```php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Testing\InteractsWithAttachments;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase, InteractsWithAttachments;
    
    public function test_user_can_upload_avatar()
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);
        
        $response = $this->actingAs($user)
            ->post('/profile/avatar', ['avatar' => $file]);
        
        $response->assertSuccessful();
        
        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertAttachmentExists($user->avatar);
        $this->assertTrue($user->avatar->isImage());
    }
    
    public function test_avatar_deleted_when_user_deleted()
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $user->avatar = $this->createFakeAttachment('image');
        $user->save();
        
        $attachment = $user->avatar;
        $user->delete();
        
        $this->assertAttachmentMissing($attachment);
    }
}
```

## Next Steps

- Explore [API Resources](api-resources.md)
- Learn about [Events](events.md)
- Configure [Filament Integration](filament.md)

