# Upgrade Guide

This release stays in the 1.x line but contains breaking changes. The root [UPGRADE.md](../UPGRADE.md) holds the full migration reference, including the exact accessor renames, removed config keys, and code samples. Read it before upgrading.

## What Changed

- **Platform floor raised** to PHP 8.5 and Laravel 13.0. Upgrade your application first.
- **Metadata support was removed.** Attachments no longer carry a custom metadata bag. Store extra data on your own model columns instead.
- **Four accessors were renamed.** The old names no longer exist; see the rename table in [UPGRADE.md](../UPGRADE.md). Stored JSON keys are unchanged, so no data migration is needed.
- **Some config keys were removed.** Republish the config to see the current file, then drop anything no longer present.
- **Events always dispatch.** The on/off config toggle is gone, and events now fire from the model observer after save or delete, each carrying the source model and attribute. See [Events](events.md).
- **Soft deletes keep their files.** Cleanup skips soft deletes now; only a force delete removes files. See [Automatic Cleanup](cleanup.md).

Republish the config:

```sh
php artisan vendor:publish --tag=attachments-config --force
```

## Getting Help

If you encounter issues during the upgrade:

1. Check the [documentation](index.md)
2. Search [GitHub Issues](https://github.com/aniftyco/laravel-attachments/issues)
3. Ask in [GitHub Discussions](https://github.com/aniftyco/laravel-attachments/discussions)
