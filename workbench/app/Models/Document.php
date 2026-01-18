<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;
use NiftyCo\Attachments\Concerns\HasAttachments;

class Document extends Model
{
    use HasAttachments;

    protected $guarded = [];

    protected $casts = [
        'file' => AsAttachment::class,
        'attachments' => AsAttachments::class,
    ];
}
