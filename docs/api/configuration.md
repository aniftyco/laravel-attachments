# Configuration Reference

Complete reference for all configuration options in `config/attachments.php`.

## Publishing Configuration

```bash
php artisan vendor:publish --tag=attachments-config
```

## Configuration File

```php
<?php

return [
    'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'public')),
    'folder' => env('ATTACHMENTS_FOLDER', 'attachments'),
    'auto_cleanup' => env('ATTACHMENTS_AUTO_CLEANUP', true),
    'delete_on_replace' => env('ATTACHMENTS_DELETE_ON_REPLACE', true),
    'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'random'),
    'temporary_url_expiration' => env('ATTACHMENTS_TEMPORARY_URL_EXPIRATION', 60),
];
```

## Options

### `disk`

**Type:** `string`  
**Default:** `'public'`  
**Environment Variable:** `ATTACHMENTS_DISK`

The default filesystem disk for storing attachments.

**Valid Values:**
- Any disk defined in `config/filesystems.php`
- Common values: `'local'`, `'public'`, `'s3'`

**Example:**
```php
'disk' => env('ATTACHMENTS_DISK', 'public'),
```

**Environment:**
```env
ATTACHMENTS_DISK=s3
```

---

### `folder`

**Type:** `string`  
**Default:** `'attachments'`  
**Environment Variable:** `ATTACHMENTS_FOLDER`

The default folder path where attachments are stored.

**Example:**
```php
'folder' => env('ATTACHMENTS_FOLDER', 'uploads'),
```

**Environment:**
```env
ATTACHMENTS_FOLDER=uploads
```

---

### `auto_cleanup`

**Type:** `boolean`  
**Default:** `true`  
**Environment Variable:** `ATTACHMENTS_AUTO_CLEANUP`

Enable automatic file deletion when models are deleted.

**Requirements:**
- Model must use `HasAttachmentCleanup` trait

**Example:**
```php
'auto_cleanup' => env('ATTACHMENTS_AUTO_CLEANUP', true),
```

**Environment:**
```env
ATTACHMENTS_AUTO_CLEANUP=false
```

---

### `delete_on_replace`

**Type:** `boolean`  
**Default:** `true`  
**Environment Variable:** `ATTACHMENTS_DELETE_ON_REPLACE`

Enable automatic deletion of old files when attachments are replaced.

**Example:**
```php
'delete_on_replace' => env('ATTACHMENTS_DELETE_ON_REPLACE', true),
```

**Environment:**
```env
ATTACHMENTS_DELETE_ON_REPLACE=false
```

---

### `naming_strategy`

**Type:** `string`  
**Default:** `'random'`  
**Environment Variable:** `ATTACHMENTS_NAMING_STRATEGY`

How stored files are named. Accepts a built-in strategy name or the fully-qualified class name of a custom strategy.

**Built-in Values:**
- `'random'` (default) - A random 40-character name plus the original extension
- `'uuid'` - A UUID name plus the original extension
- `'original'` - The sanitized client filename, with a random suffix on collision, falling back to `'random'` when there is no client name

**Custom Strategy:**

Pass the class name of any class implementing `NiftyCo\Attachments\Naming\NamingStrategy`. It is resolved from the container. See [Configuration → Naming Strategies](../configuration.md#naming-strategies).

```php
'naming_strategy' => \App\Attachments\DatePrefixedStrategy::class,
```

**Example:**
```php
'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'random'),
```

**Environment:**
```env
ATTACHMENTS_NAMING_STRATEGY=uuid
```

---

### `temporary_url_expiration`

**Type:** `integer`  
**Default:** `60`  
**Environment Variable:** `ATTACHMENTS_TEMPORARY_URL_EXPIRATION`

Default expiration time in minutes for temporary URLs.

**Example:**
```php
'temporary_url_expiration' => env('ATTACHMENTS_TEMPORARY_URL_EXPIRATION', 120),
```

**Environment:**
```env
ATTACHMENTS_TEMPORARY_URL_EXPIRATION=120
```

## Environment Variables

Quick reference for all environment variables:

```env
# Storage
ATTACHMENTS_DISK=public
ATTACHMENTS_FOLDER=attachments

# Cleanup
ATTACHMENTS_AUTO_CLEANUP=true
ATTACHMENTS_DELETE_ON_REPLACE=true

# File Naming
ATTACHMENTS_NAMING_STRATEGY=random

# URLs
ATTACHMENTS_TEMPORARY_URL_EXPIRATION=60
```

## Next Steps

- [Attachment API](attachment.md)
- [Attachments Collection API](attachments.md)

