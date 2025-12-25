<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Attachments;
use NiftyCo\Attachments\Casts\AsAttachment;
use NiftyCo\Attachments\Casts\AsAttachments;

class Post extends Model
{
    protected $guarded = [];

    protected $casts = [
        'avatar' => AsAttachment::class,
        'images' => AsAttachments::class,
    ];

    /**
     * Create the posts table for testing.
     */
    public static function createTable(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('avatar')->nullable();
            $table->text('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Drop the posts table after testing.
     */
    public static function dropTable(): void
    {
        Schema::dropIfExists('posts');
    }
}

