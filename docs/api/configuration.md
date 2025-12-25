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
    'validation' => [
        'file',
        'max:10240',
        'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,zip,rar',
    ],
    'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'hash'),
    'preserve_original_name' => env('ATTACHMENTS_PRESERVE_ORIGINAL_NAME', true),
    'temporary_url_expiration' => env('ATTACHMENTS_TEMPORARY_URL_EXPIRATION', 60),
    'events' => [
        'enabled' => env('ATTACHMENTS_EVENTS_ENABLED', true),
    ],
    'metadata' => [
        'enabled' => env('ATTACHMENTS_METADATA_ENABLED', true),
        'auto_capture' => [
            'original_name' => true,
            'uploaded_at' => true,
            'uploaded_by' => false,
        ],
    ],
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

### `validation`

**Type:** `array|string|null`  
**Default:** See below

Default validation rules for file uploads.

**Default Value:**
```php
'validation' => [
    'file',
    'max:10240', // 10MB
    'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,zip,rar',
],
```

**Array Format:**
```php
'validation' => ['file', 'image', 'max:2048'],
```

**String Format:**
```php
'validation' => 'file|image|max:2048',
```

**Disable Validation:**
```php
'validation' => null,
```

**Common Rules:**
- `'file'` - Must be a file
- `'image'` - Must be an image
- `'max:2048'` - Max size in KB
- `'min:100'` - Min size in KB
- `'mimes:jpg,png'` - Allowed extensions
- `'mimetypes:image/jpeg'` - Allowed MIME types
- `'dimensions:min_width=100'` - Image dimensions

---

### `naming_strategy`

**Type:** `string`  
**Default:** `'hash'`  
**Environment Variable:** `ATTACHMENTS_NAMING_STRATEGY`

Strategy for naming uploaded files.

**Valid Values:**
- `'hash'` - Use Laravel's hash-based naming (default)
- `'original'` - Keep original filename (sanitized)
- `'uuid'` - Generate UUID for filename

**Example:**
```php
'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'hash'),
```

**Environment:**
```env
ATTACHMENTS_NAMING_STRATEGY=uuid
```

---

### `preserve_original_name`

**Type:** `boolean`  
**Default:** `true`  
**Environment Variable:** `ATTACHMENTS_PRESERVE_ORIGINAL_NAME`

Store the original filename in metadata.

**Example:**
```php
'preserve_original_name' => env('ATTACHMENTS_PRESERVE_ORIGINAL_NAME', true),
```

**Environment:**
```env
ATTACHMENTS_PRESERVE_ORIGINAL_NAME=false
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

---

### `events.enabled`

**Type:** `boolean`  
**Default:** `true`  
**Environment Variable:** `ATTACHMENTS_EVENTS_ENABLED`

Enable or disable attachment lifecycle events.

**Example:**
```php
'events' => [
    'enabled' => env('ATTACHMENTS_EVENTS_ENABLED', true),
],
```

**Environment:**
```env
ATTACHMENTS_EVENTS_ENABLED=false
```

---

### `metadata.enabled`

**Type:** `boolean`  
**Default:** `true`  
**Environment Variable:** `ATTACHMENTS_METADATA_ENABLED`

Enable metadata support for attachments.

**Example:**
```php
'metadata' => [
    'enabled' => env('ATTACHMENTS_METADATA_ENABLED', true),
],
```

**Environment:**
```env
ATTACHMENTS_METADATA_ENABLED=false
```

---

### `metadata.auto_capture`

**Type:** `array`  
**Default:** See below

Configure which metadata fields are automatically captured.

**Default Value:**
```php
'auto_capture' => [
    'original_name' => true,
    'uploaded_at' => true,
    'uploaded_by' => false,
],
```

**Fields:**
- `original_name` - Store original filename
- `uploaded_at` - Store upload timestamp
- `uploaded_by` - Store authenticated user ID

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
ATTACHMENTS_NAMING_STRATEGY=hash
ATTACHMENTS_PRESERVE_ORIGINAL_NAME=true

# URLs
ATTACHMENTS_TEMPORARY_URL_EXPIRATION=60

# Features
ATTACHMENTS_EVENTS_ENABLED=true
ATTACHMENTS_METADATA_ENABLED=true
```

## Next Steps

- [Attachment API](attachment.md)
- [Attachments Collection API](attachments.md)

