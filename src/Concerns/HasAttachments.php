<?php

namespace NiftyCo\Attachments\Concerns;

use Illuminate\Http\UploadedFile;
use NiftyCo\Attachments\Attachment;

use function NiftyCo\Attachments\attachment_casts;

use NiftyCo\Attachments\Attachments;

trait HasAttachments
{
    use HasAttachmentCleanup;

    /**
     * Add a file to an existing collection of attachments.
     */
    public function addAttachment(
        string $attribute,
        UploadedFile $file,
        ?string $disk = null,
        ?string $folder = null
    ): static {
        $currentAttachments = $this->getAttribute($attribute);

        if (! $currentAttachments instanceof Attachments) {
            $currentAttachments = new Attachments($currentAttachments ? [$currentAttachments] : []);
        }

        $currentAttachments->attach($file, $disk, $folder);

        $this->setAttribute($attribute, $currentAttachments);

        return $this;
    }

    /**
     * Remove an attachment from a collection by name.
     */
    public function removeAttachment(string $attribute, string $name): static
    {
        $attachments = $this->getAttribute($attribute);

        if ($attachments instanceof Attachments) {
            $filtered = $attachments->filter(fn (Attachment $a) => $a->path() !== $name);
            $this->setAttribute($attribute, $filtered);
        }

        return $this;
    }

    /**
     * Clear all attachments from an attribute.
     */
    public function clearAttachments(string $attribute, bool $deleteFiles = false): static
    {
        if ($deleteFiles) {
            $attachments = $this->getAttribute($attribute);

            if ($attachments instanceof Attachments) {
                $attachments->delete();
            } elseif ($attachments instanceof Attachment) {
                $attachments->delete();
            }
        }

        $this->setAttribute($attribute, null);

        return $this;
    }

    /**
     * Get all attachment attributes for this model.
     *
     * @return array<int, string>
     */
    public function getAttachmentAttributes(): array
    {
        return array_keys(attachment_casts($this->getCasts()));
    }

    /**
     * Check if the model has any attachments.
     */
    public function hasAttachments(): bool
    {
        foreach ($this->getAttachmentAttributes() as $attribute) {
            $value = $this->getAttribute($attribute);

            if ($value instanceof Attachment) {
                return true;
            }

            if ($value instanceof Attachments && $value->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total size of all attachments on this model.
     */
    public function totalAttachmentsSize(): int
    {
        $totalSize = 0;

        foreach ($this->getAttachmentAttributes() as $attribute) {
            $value = $this->getAttribute($attribute);

            if ($value instanceof Attachment) {
                $totalSize += $value->size() ?? 0;
            }

            if ($value instanceof Attachments) {
                $totalSize += $value->totalSize();
            }
        }

        return $totalSize;
    }
}
