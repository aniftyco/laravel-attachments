<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Testing\InteractsWithAttachments;

uses(InteractsWithAttachments::class);

beforeEach(function () {
    Storage::fake('public');
});

it('can assert attachment exists', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $this->assertAttachmentExists($attachment);
});

it('can assert attachment missing', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $attachment->delete();

    $this->assertAttachmentMissing($attachment);
});

it('can assert attachment content', function () {
    $content = 'Hello, World!';
    $file = UploadedFile::fake()->createWithContent('test.txt', $content);
    $attachment = Attachment::fromFile($file, 'public', 'files');

    $this->assertAttachmentContent($attachment, $content);
});

it('can assert attachment size', function () {
    $file = UploadedFile::fake()->create('test.txt', 100); // 100KB
    $attachment = Attachment::fromFile($file, 'public', 'files');

    $this->assertAttachmentSize($attachment, $attachment->size());
});

it('can assert attachment mime type', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $this->assertAttachmentMimeType($attachment, 'image/jpeg');
});

it('can assert attachment is image', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos');

    $this->assertAttachmentIsImage($attachment);
});

it('can assert attachment is pdf', function () {
    $file = UploadedFile::fake()->create('document.pdf');
    $attachment = Attachment::fromFile($file, 'public', 'documents');

    $this->assertAttachmentIsPdf($attachment);
});

it('can assert attachment has metadata', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $attachment = Attachment::fromFile($file, 'public', 'photos')
        ->setMeta('author', 'John Doe');

    $this->assertAttachmentHasMeta($attachment, 'author', 'John Doe');
});

it('can create fake attachment', function () {
    $attachment = $this->createFakeAttachment('test.jpg', 'public', 'test', 100);

    expect($attachment)->toBeInstanceOf(Attachment::class)
        ->and($attachment->extname())->toBe('jpg')
        ->and($attachment->disk())->toBe('public');

    $this->assertAttachmentExists($attachment);
});

it('can create multiple fake attachments', function () {
    $attachments = $this->createFakeAttachments(5, 'public', 'test');

    expect($attachments)->toHaveCount(5);

    foreach ($attachments as $attachment) {
        expect($attachment)->toBeInstanceOf(Attachment::class);
        $this->assertAttachmentExists($attachment);
    }
});

it('fake attachments have sequential names', function () {
    $attachments = $this->createFakeAttachments(3);

    expect($attachments[0]->extname())->toBe('jpg')
        ->and($attachments[1]->extname())->toBe('jpg')
        ->and($attachments[2]->extname())->toBe('jpg')
        ->and($attachments)->toHaveCount(3);
});
