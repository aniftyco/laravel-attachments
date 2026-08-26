<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

use function NiftyCo\Attachments\default_disk;

it('prefers an explicit attachments.disk over the framework default', function () {
    config(['attachments.disk' => 'chosen']);
    config(['filesystems.default' => 'fallback-disk']);

    expect(default_disk())->toBe('chosen');
});

it('falls back to the framework default filesystem when attachments.disk is unset', function () {
    config(['attachments.disk' => null]);
    config(['filesystems.default' => 'not-public']);

    // No hardcoded 'public' anywhere in the resolution path.
    expect(default_disk())->toBe('not-public');
});

it('resolves fromFile disk via the framework default when attachments.disk is unset', function () {
    config(['attachments.disk' => null]);
    config(['filesystems.default' => 'default-fs']);
    Storage::fake('default-fs');

    $attachment = Attachment::fromFile(UploadedFile::fake()->image('a.jpg'));

    expect($attachment->disk())->toBe('default-fs')
        ->and($attachment->exists())->toBeTrue();
});
