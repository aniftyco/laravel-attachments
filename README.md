# Attachments for Laravel

> Turn any field on your Eloquent models into attachments with automatic file management, validation, and cleanup.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aniftyco/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/aniftyco/laravel-attachments)
[![Total Downloads](https://img.shields.io/packagist/dt/aniftyco/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/aniftyco/laravel-attachments)

## Features

- 🎯 **Simple API** - Easy-to-use casts for single and multiple attachments
- 🔄 **Automatic Cleanup** - Automatically delete files when models are deleted
- ✅ **File Validation** - Built-in validation for file size, type, and extensions
- 🔗 **URL Generation** - Generate public and temporary URLs for attachments
- 📦 **Multiple Storage Disks** - Support for any Laravel filesystem disk
- 🗂️ **Organized Storage** - Automatic folder organization with customizable paths
- 🔒 **Type Safe** - Full type hints and IDE autocomplete support

## Installation

You can install the package via Composer:

```sh
composer require aniftyco/laravel-attachments
```

Publish the configuration file (optional):

```sh
php artisan vendor:publish --tag=attachments-config
```

## Quick Start

### 1. Add a Migration

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->attachment('avatar');
    $table->timestamps();
});
```

### 2. Add the Cast to Your Model

```php
use NiftyCo\Attachments\Casts\AsAttachment;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'avatar' => AsAttachment::class,
        ];
    }
}
```

### 3. Upload a File

```php
use NiftyCo\Attachments\Attachment;

$user->avatar = Attachment::fromFile($request->file('avatar'), folder: 'avatars');
$user->save();
```

### 4. Access the Attachment

```php
echo $user->avatar->url;
echo $user->avatar->readableSize();
```

That's it! 🎉

## Testing

Run the test suite:

```sh
composer test
```

Run code style checks:

```sh
composer lint
```

Fix code style issues:

```sh
./vendor/bin/pint
```

## Contributing

Thank you for considering contributing to the Attachments for Laravel package! You can read the contribution guide [here](CONTRIBUTING.md).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
