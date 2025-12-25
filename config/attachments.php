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
    | Configure how uploaded files should be named. Options:
    | - 'hash' (default): Use Laravel's default hash-based naming
    | - 'original': Keep the original filename (sanitized)
    | - 'uuid': Generate a UUID for the filename
    |
    */

    'naming_strategy' => env('ATTACHMENTS_NAMING_STRATEGY', 'hash'),

    /*
    |--------------------------------------------------------------------------
    | Preserve Original Filename
    |--------------------------------------------------------------------------
    |
    | When true, the original filename will be stored in metadata even when
    | using hash or UUID naming strategies. This allows you to retrieve the
    | original filename later.
    |
    */

    'preserve_original_name' => env('ATTACHMENTS_PRESERVE_ORIGINAL_NAME', true),

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

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable events for attachment operations. When enabled,
    | events will be dispatched for file uploads, deletions, etc.
    |
    */

    'events' => [
        'enabled' => env('ATTACHMENTS_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    |
    | Configure metadata storage for attachments. Metadata allows you to
    | store additional information about files.
    |
    */

    'metadata' => [
        /*
        | Enable metadata support
        */
        'enabled' => env('ATTACHMENTS_METADATA_ENABLED', true),

        /*
        | Automatically capture metadata fields from uploaded files
        */
        'auto_capture' => [
            'original_name' => true,
            'uploaded_at' => true,
            'uploaded_by' => false, // Requires authentication
        ],
    ],
];
