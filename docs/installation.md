# Installation

## Requirements

Before installing Laravel Attachments, ensure your system meets the following requirements:

- PHP 8.1 or higher
- Laravel 11.0 or 12.0
- A configured filesystem disk (local, public, S3, etc.)

## Installing via Composer

Install the package using Composer:

```bash
composer require aniftyco/laravel-attachments
```

The package will automatically register its service provider through Laravel's package discovery.

## Publishing Configuration

The package works out of the box with sensible defaults. However, if you need to customize the configuration, publish the config file:

```bash
php artisan vendor:publish --tag=attachments-config
```

This will create a `config/attachments.php` file in your application.

## Setting Up Your First Attachment

### 1. Create a Migration

Add an attachment column to store attachment data:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->attachment('avatar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

**Note:** The `attachment()` macro creates a nullable JSON column. Use `attachments()` for multiple file fields.

### 2. Add the Cast to Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

$user = User::find(1);
$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars'
);
$user->save();
```

### 4. Access the Attachment

```php
// Get the URL
echo $user->avatar->url;

// Get file information
echo $user->avatar->name;
echo $user->avatar->size;
echo $user->avatar->mime;
echo $user->avatar->readableSize(); // "1.5 MB"
```

## Using Blueprint Macros (Optional)

For cleaner migrations, you can use the provided Blueprint macros:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->attachment('avatar'); // Single attachment
    $table->attachments('photos'); // Multiple attachments
    $table->timestamps();
});
```

These macros automatically create nullable JSON columns.

## Configuring Storage

By default, attachments are stored on the `public` disk in an `attachments` folder. You can customize this in your `.env` file:

```env
ATTACHMENTS_DISK=public
ATTACHMENTS_FOLDER=attachments
```

Or in the published configuration file:

```php
// config/attachments.php
return [
    'disk' => env('ATTACHMENTS_DISK', 'public'),
    'folder' => env('ATTACHMENTS_FOLDER', 'attachments'),
    // ...
];
```

## Next Steps

- Learn about [Single Attachments](single-attachments.md)
- Learn about [Multiple Attachments](multiple-attachments.md)
- Configure [File Validation](validation.md)
- Set up [Automatic Cleanup](cleanup.md)
- Explore [Configuration Options](configuration.md)
