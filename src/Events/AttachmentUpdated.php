<?php

namespace NiftyCo\Attachments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NiftyCo\Attachments\Attachment;

class AttachmentUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Attachment $attachment,
        public ?Attachment $oldAttachment = null,
        public ?string $modelClass = null,
        public ?string $modelId = null,
        public ?string $attribute = null
    ) {
    }
}

