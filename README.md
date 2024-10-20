# Attachments for Laravel

> Turn any field on your Eloquent models into attachments

> [!WARNING]
> This package is not ready for general consumption

## Installation

You can install the package via Composer:

```sh
composer require aniftyco/laravel-attachments:dev-master
```

## Usage

### Adding Attachments to Models

To add attachments to your Eloquent models, use the provided cast classes.

#### Single Attachment

Use the `AsAttachment` cast to handle a single attachment:

```php
use NiftyCo\Attachments\Casts\AsAttachment;

class User extends Model
{
    protected $casts = [
        'avatar' => AsAttachment::class,
    ];
}
```

To set an image as an attachment on your model:

```php
use NiftyCo\Attachments\Attachment;

class UserController
{
    public function store(UserStoreRequest $request, User $user)
    {
        $user->avatar = Attachment::fromFile($request->file('avatar'), folder: 'avatars');

        $user->save();

        // ...
    }
}
```

#### Multiple Attachments

Use the `AsAttachments` cast to handle multiple attachments:

```php
use NiftyCo\Attachments\Casts\AsAttachments;

class Post extends Model
{
    protected $casts = [
        'images' => AsAttachments::class,
    ];
}
```

To attach multiple attachments to your model:

```php
class PostController
{
    public function store(PostStoreRequest $request, Post $post)
    {

        $images = $request->file('images');

        // Loop over all images uploaded and add to the
        // collection of images already on the post
        array_map(function($image) use ($post) {
            $post->images->addFromFile($image);
        }, $images);

        // Save post
        $post->save();

        // ...
    }
}
```

## Contributing

Thank you for considering contributing to the Attachments for Laravel package! You can read the contribution guide [here](CONTRIBUTING.md).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
