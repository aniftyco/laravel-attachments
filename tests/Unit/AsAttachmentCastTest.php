<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Casts\AsAttachment;

beforeEach(function () {
    Storage::fake('public');
});

it('casts null to null', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $result = $cast->get($model, 'avatar', null, []);

    expect($result)->toBeNull();
});

it('casts empty value to null', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $result = $cast->get($model, 'avatar', '{}', ['avatar' => '{}']);

    expect($result)->toBeNull();
});

it('casts valid JSON to Attachment', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $json = json_encode([
        'disk' => 'public',
        'name' => 'test.jpg',
        'size' => 1024,
        'extname' => 'jpg',
        'mimeType' => 'image/jpeg',
    ]);

    $result = $cast->get($model, 'avatar', $json, ['avatar' => $json]);

    expect($result)->toBeInstanceOf(Attachment::class);
    expect($result->disk())->toBe('public');
    expect($result->path())->toBe('test.jpg');
    expect($result->size())->toBe(1024);
});

it('returns null for invalid JSON', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $result = $cast->get($model, 'avatar', 'invalid json', ['avatar' => 'invalid json']);

    expect($result)->toBeNull();
});

it('returns null for JSON missing required fields', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $json = json_encode(['size' => 1024]); // Missing disk and name

    $result = $cast->get($model, 'avatar', $json, ['avatar' => $json]);

    expect($result)->toBeNull();
});

it('sets Attachment to JSON string', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = Attachment::fromFile($file, 'public');

    $result = $cast->set($model, 'avatar', $attachment, []);

    expect($result)->toBeJson();

    $decoded = json_decode($result, true);
    expect($decoded)->toHaveKeys(['disk', 'name', 'size', 'extname', 'mimeType']);
});

it('sets null to null', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $result = $cast->set($model, 'avatar', null, []);

    expect($result)->toBeNull();
});

it('sets non-Attachment value to null', function () {
    $cast = new AsAttachment;
    $model = new class extends Model {};

    $result = $cast->set($model, 'avatar', 'not an attachment', []);

    expect($result)->toBeNull();
});
