<?php

namespace NiftyCo\Attachments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NiftyCo\Attachments\Attachment;

class AttachmentCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Attachment $attachment,
        public ?string $modelClass = null,
        public ?string $modelId = null,
        public ?string $attribute = null
    ) {}
}
