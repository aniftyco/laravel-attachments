<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

beforeEach(function () {
    Storage::fake('public');
});

it('can create an attachment from an uploaded file', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $attachment = Attachment::fromFile($file, 'public', 'uploads');

    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->disk())->toBe('public');
    expect($attachment->extension())->toBe('jpg');
    expect($attachment->mimeType())->toBe('image/jpeg');
    expect($attachment->size())->toBeGreaterThan(0);
});

it('stores the file in the correct location', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public', 'uploads');

    expect($attachment->exists())->toBeTrue();
    Storage::disk('public')->assertExists($attachment->path());
});

it('can check if attachment exists', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public');

    expect($attachment->exists())->toBeTrue();
});

it('returns false when checking existence of non-existent file', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'non-existent.jpg',
        size: 1024,
        extname: 'jpg',
        mimeType: 'image/jpeg'
    );

    expect($attachment->exists())->toBeFalse();
});

it('can delete an attachment', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public');

    expect($attachment->exists())->toBeTrue();

    $result = $attachment->delete();

    expect($result)->toBeTrue();
    expect($attachment->exists())->toBeFalse();
});

it('can delete non-existent attachment without error', function () {
    $attachment = new Attachment(
        disk: 'public',
        name: 'non-existent.jpg',
        size: 1024,
        extname: 'jpg',
        mimeType: 'image/jpeg'
    );

    // Storage::delete() returns true even for non-existent files
    $result = $attachment->delete();

    expect($result)->toBeTrue();
});

it('provides access to file properties', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500);

    $attachment = Attachment::fromFile($file, 'public');

    expect($attachment->disk())->toBe('public');
    expect($attachment->extension())->toBe('pdf');
    expect($attachment->mimeType())->toBe('application/pdf');
    expect($attachment->size())->toBeGreaterThan(0);
    expect($attachment->path())->toContain('attachments');
});

it('can serialize to array', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public');

    $array = $attachment->toArray();

    expect($array)->toHaveKeys(['disk', 'name', 'size', 'extname', 'mimeType']);
    expect($array['disk'])->toBe('public');
    expect($array['extname'])->toBe('jpg');
});

it('can serialize to JSON', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public');

    $json = $attachment->toJson();

    expect($json)->toBeJson();

    $decoded = json_decode($json, true);
    expect($decoded)->toHaveKeys(['disk', 'name', 'size', 'extname', 'mimeType']);
});

it('handles null values gracefully in constructor', function () {
    $attachment = new Attachment(
        disk: null,
        name: null,
        size: null,
        extname: null,
        mimeType: null
    );

    expect($attachment->exists())->toBeFalse();
    expect($attachment->delete())->toBeFalse();
    expect($attachment->disk())->toBeNull();
    expect($attachment->path())->toBeNull();
});

it('generates a URL for the attachment', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = Attachment::fromFile($file, 'public');

    $url = $attachment->url();

    expect($url)->toBeString();
    expect($url)->toContain('storage');
});
