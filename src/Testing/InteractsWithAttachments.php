<?php

namespace NiftyCo\Attachments\Testing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

trait InteractsWithAttachments
{
    /**
     * Assert that an attachment exists in storage.
     */
    public function assertAttachmentExists(Attachment $attachment): void
    {
        $disk = $attachment->disk();
        $path = $attachment->path();

        $this->assertTrue(
            Storage::disk($disk)->exists($path),
            "Failed asserting that attachment [{$path}] exists on disk [{$disk}]."
        );
    }

    /**
     * Assert that an attachment does not exist in storage.
     */
    public function assertAttachmentMissing(Attachment $attachment): void
    {
        $disk = $attachment->disk();
        $path = $attachment->path();

        $this->assertFalse(
            Storage::disk($disk)->exists($path),
            "Failed asserting that attachment [{$path}] does not exist on disk [{$disk}]."
        );
    }

    /**
     * Assert that an attachment has the expected content.
     */
    public function assertAttachmentContent(Attachment $attachment, string $expectedContent): void
    {
        $actualContent = $attachment->contents();

        $this->assertEquals(
            $expectedContent,
            $actualContent,
            "Failed asserting that attachment [{$attachment->path()}] has expected content."
        );
    }

    /**
     * Assert that an attachment has the expected size.
     */
    public function assertAttachmentSize(Attachment $attachment, int $expectedSize): void
    {
        $this->assertEquals(
            $expectedSize,
            $attachment->size(),
            "Failed asserting that attachment [{$attachment->path()}] has size [{$expectedSize}]."
        );
    }

    /**
     * Assert that an attachment has the expected MIME type.
     */
    public function assertAttachmentMimeType(Attachment $attachment, string $expectedMimeType): void
    {
        $this->assertEquals(
            $expectedMimeType,
            $attachment->mimeType(),
            "Failed asserting that attachment [{$attachment->path()}] has MIME type [{$expectedMimeType}]."
        );
    }

    /**
     * Assert that an attachment is an image.
     */
    public function assertAttachmentIsImage(Attachment $attachment): void
    {
        $this->assertTrue(
            $attachment->isImage(),
            "Failed asserting that attachment [{$attachment->path()}] is an image."
        );
    }

    /**
     * Assert that an attachment is a PDF.
     */
    public function assertAttachmentIsPdf(Attachment $attachment): void
    {
        $this->assertTrue(
            $attachment->isPdf(),
            "Failed asserting that attachment [{$attachment->path()}] is a PDF."
        );
    }

    /**
     * Assert that an attachment has metadata.
     */
    public function assertAttachmentHasMeta(Attachment $attachment, string $key, mixed $expectedValue = null): void
    {
        $this->assertTrue(
            $attachment->hasMeta($key),
            "Failed asserting that attachment [{$attachment->path()}] has metadata key [{$key}]."
        );

        if ($expectedValue !== null) {
            $this->assertEquals(
                $expectedValue,
                $attachment->getMeta($key),
                "Failed asserting that attachment metadata [{$key}] equals expected value."
            );
        }
    }

    /**
     * Create a fake attachment for testing.
     */
    public function createFakeAttachment(
        string $name = 'test.jpg',
        string $disk = 'public',
        string $folder = 'test',
        int $sizeKb = 100
    ): Attachment {
        Storage::fake($disk);

        $file = UploadedFile::fake()->create($name, $sizeKb);

        return Attachment::fromFile($file, $disk, $folder);
    }

    /**
     * Create multiple fake attachments for testing.
     *
     * @return array<Attachment>
     */
    public function createFakeAttachments(
        int $count = 3,
        string $disk = 'public',
        string $folder = 'test'
    ): array {
        Storage::fake($disk);

        $attachments = [];

        for ($i = 1; $i <= $count; $i++) {
            $file = UploadedFile::fake()->image("test-{$i}.jpg");
            $attachments[] = Attachment::fromFile($file, $disk, $folder);
        }

        return $attachments;
    }
}
