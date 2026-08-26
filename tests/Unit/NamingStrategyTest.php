<?php

use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Exceptions\AttachmentException;
use NiftyCo\Attachments\Naming\AttachmentContext;
use NiftyCo\Attachments\Naming\NamingStrategy;
use NiftyCo\Attachments\Naming\NamingStrategyResolver;
use NiftyCo\Attachments\Naming\OriginalNamingStrategy;
use NiftyCo\Attachments\Naming\RandomNamingStrategy;
use NiftyCo\Attachments\Naming\UuidNamingStrategy;
use Tests\Fixtures\FixedNamingStrategy;

beforeEach(function () {
    Storage::fake('public');
});

function namingContext(array $overrides = []): AttachmentContext
{
    return new AttachmentContext(
        originalName: array_key_exists('originalName', $overrides) ? $overrides['originalName'] : 'My Photo.jpg',
        extension: array_key_exists('extension', $overrides) ? $overrides['extension'] : 'jpg',
        mimeType: $overrides['mimeType'] ?? 'image/jpeg',
        size: $overrides['size'] ?? 100,
        folder: $overrides['folder'] ?? 'attachments',
        disk: $overrides['disk'] ?? 'public',
    );
}

it('random strategy produces a random basename with the extension under the folder', function () {
    $path = (new RandomNamingStrategy)(namingContext());

    expect($path)->toStartWith('attachments/')
        ->and($path)->toEndWith('.jpg')
        ->and(basename($path, '.jpg'))->toHaveLength(40);
});

it('uuid strategy produces a uuid basename with the extension under the folder', function () {
    $path = (new UuidNamingStrategy)(namingContext());

    expect($path)->toStartWith('attachments/')
        ->and($path)->toEndWith('.jpg')
        ->and(basename($path, '.jpg'))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('original strategy sanitizes the original filename', function () {
    $path = (new OriginalNamingStrategy)(namingContext());

    expect($path)->toBe('attachments/My-Photo.jpg');
});

it('original strategy falls back to random naming when there is no original name', function () {
    $path = (new OriginalNamingStrategy)(namingContext(['originalName' => null]));

    expect($path)->toStartWith('attachments/')
        ->and($path)->toEndWith('.jpg')
        ->and(basename($path, '.jpg'))->toHaveLength(40);
});

it('original strategy generates a distinct name on collision', function () {
    Storage::disk('public')->put('attachments/My-Photo.jpg', 'existing');

    $path = (new OriginalNamingStrategy)(namingContext());

    expect($path)->not->toBe('attachments/My-Photo.jpg')
        ->and($path)->toStartWith('attachments/My-Photo-')
        ->and($path)->toEndWith('.jpg');
});

it('resolves each built-in strategy by name', function () {
    expect(NamingStrategyResolver::resolve('random'))->toBeInstanceOf(RandomNamingStrategy::class)
        ->and(NamingStrategyResolver::resolve('uuid'))->toBeInstanceOf(UuidNamingStrategy::class)
        ->and(NamingStrategyResolver::resolve('original'))->toBeInstanceOf(OriginalNamingStrategy::class);
});

it('resolves a custom strategy FQN from the container with its dependencies', function () {
    $strategy = NamingStrategyResolver::resolve(FixedNamingStrategy::class);

    expect($strategy)->toBeInstanceOf(NamingStrategy::class)
        ->and($strategy)->toBeInstanceOf(FixedNamingStrategy::class);

    expect($strategy(namingContext()))->toBe('attachments/custom-fixed.jpg');
});

it('throws for an unknown or invalid strategy', function () {
    expect(fn () => NamingStrategyResolver::resolve('not-a-real-strategy'))
        ->toThrow(AttachmentException::class);

    expect(fn () => NamingStrategyResolver::resolve(stdClass::class))
        ->toThrow(AttachmentException::class);
});
