# Laravel Attachments Documentation

Welcome to the Laravel Attachments documentation. This package provides a simple and elegant way to handle file attachments in your Laravel applications.

## What is Laravel Attachments?

Laravel Attachments turns any field on your Eloquent models into attachments with automatic file management and cleanup. It provides a type-safe, developer-friendly API for handling single or multiple file uploads.

## Key Features

- 🎯 **Simple API** - Easy-to-use casts for single and multiple attachments
- 🔄 **Automatic Cleanup** - Automatically delete files when models are deleted
- 🔗 **URL Generation** - Generate public and temporary URLs for attachments
- 📦 **Multiple Storage Disks** - Support for any Laravel filesystem disk
- 🗂️ **Organized Storage** - Automatic folder organization with customizable paths
- 🏷️ **Naming Strategies** - Built-in random, UUID, and original strategies, plus your own
- 🔒 **Type Safe** - Full type hints and IDE autocomplete support
- 🎨 **Filament Integration** - Ready-to-use Filament form fields and table columns
- 🧪 **Testing Helpers** - Built-in testing utilities for easy test writing
- 📡 **Events** - Listen to attachment lifecycle events

## Quick Start

### Installation

```bash
composer require aniftyco/laravel-attachments
```

### Basic Usage

Add an attachment column to your migration:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->attachment('avatar');
    $table->timestamps();
});
```

Add the cast to your model:

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

Upload a file:

```php
use NiftyCo\Attachments\Attachment;

$user->avatar = Attachment::fromFile($request->file('avatar'), folder: 'avatars');
$user->save();
```

Access the attachment:

```php
echo $user->avatar->url();
echo $user->avatar->readableSize();
```

## Documentation Sections

### Getting Started

- [Installation](installation.md)
- [Configuration](configuration.md)

### Core Concepts

- [Single Attachments](single-attachments.md)
- [Multiple Attachments](multiple-attachments.md)
- [Storage & Disks](storage.md)

### Advanced Features

- [Automatic Cleanup](cleanup.md)
- [URL Generation](urls.md)
- [Events](events.md)
- [API Resources](api-resources.md)

### Integrations

- [Filament](filament.md)
- [Testing](testing.md)

### Reference

- [Attachment API](api/attachment.md)
- [Attachments Collection API](api/attachments.md)
- [Configuration Reference](api/configuration.md)

## Requirements

- PHP 8.5 or higher
- Laravel 13.0 or higher

## Support

- [GitHub Issues](https://github.com/aniftyco/laravel-attachments/issues)
- [GitHub Discussions](https://github.com/aniftyco/laravel-attachments/discussions)

## License

Laravel Attachments is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
