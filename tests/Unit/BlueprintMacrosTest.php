<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('can add attachment column using macro', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachment();
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'attachment'))->toBeTrue();

    Schema::dropIfExists('test_table');
});

it('can add attachment column with custom name', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachment('avatar');
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'avatar'))->toBeTrue();

    Schema::dropIfExists('test_table');
});

it('can add attachments column using macro', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachments();
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'attachments'))->toBeTrue();

    Schema::dropIfExists('test_table');
});

it('can add attachments column with custom name', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachments('photos');
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'photos'))->toBeTrue();

    Schema::dropIfExists('test_table');
});

it('can drop attachment column using macro', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachment();
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'attachment'))->toBeTrue();

    Schema::table('test_table', function (Blueprint $table) {
        $table->dropAttachment();
    });

    expect(Schema::hasColumn('test_table', 'attachment'))->toBeFalse();

    Schema::dropIfExists('test_table');
});

it('can drop attachments column using macro', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachments();
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'attachments'))->toBeTrue();

    Schema::table('test_table', function (Blueprint $table) {
        $table->dropAttachments();
    });

    expect(Schema::hasColumn('test_table', 'attachments'))->toBeFalse();

    Schema::dropIfExists('test_table');
});

it('can add multiple attachment columns', function () {
    Schema::create('test_table', function (Blueprint $table) {
        $table->id();
        $table->attachment('avatar');
        $table->attachment('cover');
        $table->attachments('photos');
        $table->attachments('documents');
        $table->timestamps();
    });

    expect(Schema::hasColumn('test_table', 'avatar'))->toBeTrue()
        ->and(Schema::hasColumn('test_table', 'cover'))->toBeTrue()
        ->and(Schema::hasColumn('test_table', 'photos'))->toBeTrue()
        ->and(Schema::hasColumn('test_table', 'documents'))->toBeTrue();

    Schema::dropIfExists('test_table');
});

