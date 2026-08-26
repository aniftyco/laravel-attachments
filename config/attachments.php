<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage disk that will be used when
    | storing attachments. You can override this on a per-attachment basis
    | by passing a disk parameter to the fromFile() method.
    |
    */

    'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'public')),

    /*
    |--------------------------------------------------------------------------
    | Default Storage Folder
    |--------------------------------------------------------------------------
    |
    | This option controls the default folder where attachments will be stored.
    | You can override this on a per-attachment basis by passing a folder
    | parameter to the fromFile() method.
    |
    */

    'folder' => env('ATTACHMENTS_FOLDER', 'attachments'),

    /*
    |--------------------------------------------------------------------------
    | Auto Cleanup
    |--------------------------------------------------------------------------
    |
    | When enabled, attachments will be automatically deleted from storage
    | when the parent model is deleted. This uses a model observer to detect
    | model deletion and clean up associated files.
    |
    */

    'auto_cleanup' => env('ATTACHMENTS_AUTO_CLEANUP', true),

    /*
    |--------------------------------------------------------------------------
    | Delete on Replace
    |--------------------------------------------------------------------------
    |
    | When enabled, the old attachment file will be automatically deleted from
    | storage when it's replaced with a new attachment. This prevents orphaned
    | files from accumulating in storage.
    |
    */

    'delete_on_replace' => env('ATTACHMENTS_DELETE_ON_REPLACE', true),

    /*
    |--------------------------------------------------------------------------
    | File Naming Strategy
    |--------------------------------------------------------------------------
    |
    | Configure how uploaded files should be named. Accepts one of the built-in
    | strategies — 'random', 'uuid', or 'original' — or the fully-qualified class
    | name of a class implementing NiftyCo\Attachments\Naming\NamingStrategy.
    |
    | - 'random' (default): Random basename + original extension
    | - 'uuid': UUID basename + original extension
    | - 'original': Sanitized original filename (unique on collision)
    |
    */

    'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'random'),

    /*
    |--------------------------------------------------------------------------
    | Temporary URL Expiration
    |--------------------------------------------------------------------------
    |
    | The default expiration time (in minutes) for temporary URLs generated
    | for private files. This only applies to disks that support temporary
    | URLs (like S3).
    |
    */

    'temporary_url_expiration' => env('ATTACHMENTS_TEMPORARY_URL_EXPIRATION', 60),
];
