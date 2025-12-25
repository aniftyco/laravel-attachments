<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Casts\AsAttachments;

beforeEach(function () {
    Storage::fake('public');
});

it('casts null to empty Attachments', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $result = $cast->get($model, 'images', null, []);

    expect($result)->toBeInstanceOf(Attachments::class);
    expect($result->count())->toBe(0);
});

it('casts empty array to empty Attachments', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $result = $cast->get($model, 'images', '[]', ['images' => '[]']);

    expect($result)->toBeInstanceOf(Attachments::class);
    expect($result->count())->toBe(0);
});

it('casts valid JSON array to Attachments collection', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $json = json_encode([
        [
            'disk' => 'public',
            'name' => 'test1.jpg',
            'size' => 1024,
            'extname' => 'jpg',
            'mimeType' => 'image/jpeg',
        ],
        [
            'disk' => 'public',
            'name' => 'test2.png',
            'size' => 2048,
            'extname' => 'png',
            'mimeType' => 'image/png',
        ],
    ]);

    $result = $cast->get($model, 'images', $json, ['images' => $json]);

    expect($result)->toBeInstanceOf(Attachments::class);
    expect($result->count())->toBe(2);
    expect($result->first())->toBeInstanceOf(Attachment::class);
    expect($result->first()->path())->toBe('test1.jpg');
    expect($result->last()->path())->toBe('test2.png');
});

it('skips invalid items in array', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $json = json_encode([
        [
            'disk' => 'public',
            'name' => 'test1.jpg',
            'size' => 1024,
            'extname' => 'jpg',
            'mimeType' => 'image/jpeg',
        ],
        ['invalid' => 'data'], // Missing required fields
        [
            'disk' => 'public',
            'name' => 'test2.png',
            'size' => 2048,
            'extname' => 'png',
            'mimeType' => 'image/png',
        ],
    ]);

    $result = $cast->get($model, 'images', $json, ['images' => $json]);

    expect($result)->toBeInstanceOf(Attachments::class);
    expect($result->count())->toBe(2); // Only valid items
});

it('sets Attachments to JSON string', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $file1 = UploadedFile::fake()->image('test1.jpg');
    $file2 = UploadedFile::fake()->image('test2.jpg');

    $attachments = new Attachments([
        Attachment::fromFile($file1, 'public'),
        Attachment::fromFile($file2, 'public'),
    ]);

    $result = $cast->set($model, 'images', $attachments, []);

    expect($result)->toBeJson();

    $decoded = json_decode($result, true);
    expect($decoded)->toBeArray();
    expect(count($decoded))->toBe(2);
});

it('sets null to empty JSON array', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $result = $cast->set($model, 'images', null, []);

    expect($result)->toBe('[]');
});

it('sets non-Attachments value to empty JSON array', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $result = $cast->set($model, 'images', 'not a collection', []);

    expect($result)->toBe('[]');
});

it('handles empty Attachments', function () {
    $cast = new AsAttachments;
    $model = new class extends Model {};

    $attachments = new Attachments([]);

    $result = $cast->set($model, 'images', $attachments, []);

    expect($result)->toBe('[]');
});
