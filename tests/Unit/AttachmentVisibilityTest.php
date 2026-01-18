<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

beforeEach(function () {
    Storage::fake('public-disk');
    Storage::fake('private-disk');
});

it('stores file with public visibility when disk visibility is public', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');

    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public-disk', 'uploads');

    expect(Storage::disk('public-disk')->getVisibility($attachment->path()))->toBe('public');
});

it('uses storePublicly for public disk and store for non-public disk', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $publicFile = UploadedFile::fake()->image('public.jpg');
    $privateFile = UploadedFile::fake()->image('private.jpg');

    $publicAttachment = Attachment::fromFile($publicFile, 'public-disk', 'uploads');
    $privateAttachment = Attachment::fromFile($privateFile, 'private-disk', 'uploads');

    // Both files should exist
    expect($publicAttachment->exists())->toBeTrue();
    expect($privateAttachment->exists())->toBeTrue();

    // Public disk file should have public visibility
    expect(Storage::disk('public-disk')->getVisibility($publicAttachment->path()))->toBe('public');
});

it('sets public visibility when moving to public disk', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'private-disk', 'uploads');

    $moved = $attachment->move('public-disk', 'moved');

    expect(Storage::disk('public-disk')->getVisibility($moved->path()))->toBe('public');
});

it('sets private visibility when moving to private disk', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'public-disk', 'uploads');

    $moved = $attachment->move('private-disk', 'moved');

    expect(Storage::disk('private-disk')->getVisibility($moved->path()))->toBe('private');
});

it('sets public visibility when duplicating to public disk', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'private-disk', 'uploads');

    $duplicate = $attachment->duplicate('public-disk', 'duplicates');

    expect(Storage::disk('public-disk')->getVisibility($duplicate->path()))->toBe('public');
});

it('sets private visibility when duplicating to private disk', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'public-disk', 'uploads');

    $duplicate = $attachment->duplicate('private-disk', 'duplicates');

    expect(Storage::disk('private-disk')->getVisibility($duplicate->path()))->toBe('private');
});

it('move preserves file content across disks with different visibility', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $file = UploadedFile::fake()->createWithContent('test.txt', 'Hello World');
    $attachment = Attachment::fromFile($file, 'private-disk', 'uploads');

    $moved = $attachment->move('public-disk', 'moved');

    expect($moved->contents())->toBe('Hello World');
    expect(Storage::disk('public-disk')->getVisibility($moved->path()))->toBe('public');
});

it('duplicate preserves file content across disks with different visibility', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Config::set('filesystems.disks.private-disk.visibility', 'private');

    $file = UploadedFile::fake()->createWithContent('test.txt', 'Hello World');
    $attachment = Attachment::fromFile($file, 'private-disk', 'uploads');

    $duplicate = $attachment->duplicate('public-disk', 'duplicates');

    // Original should still exist
    expect($attachment->exists())->toBeTrue();
    expect($attachment->contents())->toBe('Hello World');

    // Duplicate should exist with correct visibility
    expect($duplicate->exists())->toBeTrue();
    expect($duplicate->contents())->toBe('Hello World');
    expect(Storage::disk('public-disk')->getVisibility($duplicate->path()))->toBe('public');
});

it('defaults to private visibility when disk has no visibility configured', function () {
    Storage::fake('no-visibility-disk');
    // Note: NOT setting any visibility in config

    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'no-visibility-disk', 'uploads');

    // Should default to private (store, not storePublicly)
    expect($attachment->exists())->toBeTrue();
    expect(Storage::disk('no-visibility-disk')->getVisibility($attachment->path()))->toBe('private');
});

it('move defaults to private visibility when target disk has no visibility configured', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Storage::fake('public-disk');
    Storage::fake('no-visibility-disk');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'public-disk', 'uploads');

    $moved = $attachment->move('no-visibility-disk', 'moved');

    expect($moved->exists())->toBeTrue();
    expect(Storage::disk('no-visibility-disk')->getVisibility($moved->path()))->toBe('private');
});

it('duplicate defaults to private visibility when target disk has no visibility configured', function () {
    Config::set('filesystems.disks.public-disk.visibility', 'public');
    Storage::fake('public-disk');
    Storage::fake('no-visibility-disk');

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'public-disk', 'uploads');

    $duplicate = $attachment->duplicate('no-visibility-disk', 'duplicates');

    expect($duplicate->exists())->toBeTrue();
    expect(Storage::disk('no-visibility-disk')->getVisibility($duplicate->path()))->toBe('private');
});
