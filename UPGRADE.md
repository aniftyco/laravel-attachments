# Upgrade Guide

## Upgrading Within 1.x

This release stays in the 1.x line but contains breaking changes. Read this guide before upgrading and update your code where it applies.

### Platform Requirements

The minimum platform has moved up:

- PHP 8.5 or higher
- Laravel 13.0 or higher

Upgrade your application to these versions first.

### Removed: The Metadata API

The entire metadata concept has been removed. Attachments no longer carry a custom metadata bag.

Remove any use of:

- `Attachment::fromFile(..., metadata: [...])` and `fromFiles(..., metadata: [...])`
- `$attachment->metadata()`, `setMetadata()`, `hasMetadata()`
- `withMetadata()`, `getMeta()`, `setMeta()`, `hasMeta()`, `removeMeta()`
- The `metadata` block in `config/attachments.php`

If you relied on storing extra data alongside a file (an uploader ID, a description), keep that data on your own model column or a related table instead.

### Renamed Accessors

Four accessors were renamed. The old names no longer exist and calling them is a fatal error.

| Old            | New              |
| -------------- | ---------------- |
| `name()`       | `path()`         |
| `mimeType()`   | `mime()`         |
| `extname()`    | `extension()`    |
| `tempUrl()`    | `temporaryUrl()` |

Search your codebase and views for these calls and update each one:

```php
// Before
$attachment->name();
$attachment->mimeType();
$attachment->extname();
$attachment->tempUrl(now()->addHour());

// After
$attachment->path();
$attachment->mime();
$attachment->extension();
$attachment->temporaryUrl(now()->addHour());
```

The stored JSON keys are unchanged (`disk`, `name`, `size`, `extname`, `mimeType`), so no data migration is needed. Only the PHP accessors changed.

### Removed Config Keys

Delete these keys from your published `config/attachments.php`:

- `preserve_original_name`
- The entire `events` block (`events.enabled`)
- The entire `metadata` block

The current keys are `disk`, `folder`, `auto_cleanup`, `delete_on_replace`, `naming_strategy`, and `temporary_url_expiration`. Republish the config to see the current file:

```sh
php artisan vendor:publish --tag=attachments-config --force
```

### Events Are Always Dispatched

There is no longer an `events.enabled` toggle. Events always dispatch; if you do not register a listener, nothing happens.

Events now fire from the model observer after the row is saved or deleted, not from the casts. The model's key is therefore always available on the event. Each event carries the source model and attribute:

- `AttachmentCreated` and `AttachmentDeleted` receive `($attachment, $modelClass, $modelId, $attribute)`.
- `AttachmentUpdated` also receives the replaced file: `($attachment, $oldAttachment, $modelClass, $modelId, $attribute)`.

If your listeners read attachment properties directly (`$event->attachment->name`), switch to the accessor methods (`$event->attachment->path()`). See [Events](docs/events.md).

### Soft Deletes Keep Their Files

File cleanup now skips soft deletes. A model using `SoftDeletes` that is soft-deleted keeps its files and fires no `AttachmentDeleted` event, so the record and its attachments can still be restored.

Only a force delete, or a hard delete on a model that does not use `SoftDeletes`, purges the files and fires the event. If you were relying on a soft delete removing files, use `forceDelete()` instead. See [Automatic Cleanup](docs/cleanup.md).
