<?php

use Illuminate\Support\Facades\Storage;
use NiftyCo\Attachments\Attachment;

use function NiftyCo\Attachments\extract_extension;

use NiftyCo\Attachments\Http\Resources\AttachmentResource;

function att(?string $mime, string $ext = 'bin'): Attachment
{
    return new Attachment('public', "file.{$ext}", 10, $ext, $mime);
}

it('detects archives across formats', function () {
    expect(att('application/zip')->isArchive())->toBeTrue()
        ->and(att('application/x-tar')->isArchive())->toBeTrue()
        ->and(att('application/gzip')->isArchive())->toBeTrue()
        ->and(att('application/x-gzip')->isArchive())->toBeTrue()
        ->and(att('application/x-compressed-tar')->isArchive())->toBeTrue()
        ->and(att('application/x-7z-compressed')->isArchive())->toBeTrue()
        ->and(att('application/vnd.rar')->isArchive())->toBeTrue()
        ->and(att('application/x-rar-compressed')->isArchive())->toBeTrue()
        ->and(att('application/x-bzip2')->isArchive())->toBeTrue()
        ->and(att('application/x-xz')->isArchive())->toBeTrue()
        ->and(att('image/jpeg')->isArchive())->toBeFalse()
        ->and(att(null)->isArchive())->toBeFalse();
});

it('detects text including json and xml', function () {
    expect(att('text/plain')->isText())->toBeTrue()
        ->and(att('text/markdown')->isText())->toBeTrue()
        ->and(att('application/json')->isText())->toBeTrue()
        ->and(att('application/xml')->isText())->toBeTrue()
        ->and(att('image/png')->isText())->toBeFalse()
        ->and(att(null)->isText())->toBeFalse();
});

it('detects documents including opendocument and rtf', function () {
    expect(att('application/pdf')->isDocument())->toBeTrue()
        ->and(att('application/msword')->isDocument())->toBeTrue()
        ->and(att('application/vnd.oasis.opendocument.text')->isDocument())->toBeTrue()
        ->and(att('application/vnd.oasis.opendocument.spreadsheet')->isDocument())->toBeTrue()
        ->and(att('application/vnd.oasis.opendocument.presentation')->isDocument())->toBeTrue()
        ->and(att('application/rtf')->isDocument())->toBeTrue()
        ->and(att('text/csv')->isDocument())->toBeTrue()
        ->and(att('image/png')->isDocument())->toBeFalse();
});

it('resolves the category in priority order', function () {
    expect(att('image/png')->type())->toBe('image')
        ->and(att('video/mp4')->type())->toBe('video')
        ->and(att('audio/mpeg')->type())->toBe('audio')
        ->and(att('application/pdf')->type())->toBe('pdf')          // pdf wins over document
        ->and(att('application/zip')->type())->toBe('archive')      // archive wins over document
        ->and(att('application/x-compressed-tar')->type())->toBe('archive')
        ->and(att('application/vnd.oasis.opendocument.text')->type())->toBe('document')
        ->and(att('text/csv')->type())->toBe('document')            // document wins over text
        ->and(att('text/markdown')->type())->toBe('text')
        ->and(att('application/json')->type())->toBe('text')
        ->and(att('application/octet-stream')->type())->toBe('other')
        ->and(att(null)->type())->toBe('other');
});

it('emits the full category vocabulary from the resource type field', function () {
    $cases = [
        'image/png' => 'image',
        'video/mp4' => 'video',
        'audio/mpeg' => 'audio',
        'application/pdf' => 'pdf',
        'application/zip' => 'archive',
        'application/msword' => 'document',
        'text/markdown' => 'text',
        'application/octet-stream' => 'other',
    ];

    foreach ($cases as $mime => $expected) {
        $array = (new AttachmentResource(att($mime)))->toArray(request());
        expect($array['type'])->toBe($expected);
    }
});

it('extracts compound extensions', function () {
    expect(extract_extension('backup.tar.gz'))->toBe('tar.gz')
        ->and(extract_extension('logs.tar.bz2'))->toBe('tar.bz2')
        ->and(extract_extension('data.tar.xz'))->toBe('tar.xz')
        ->and(extract_extension('photo.JPG'))->toBe('JPG')
        ->and(extract_extension('archive.zip'))->toBe('zip')
        ->and(extract_extension('README'))->toBeNull();
});

it('stores a compound-extension file as an archive via fromContent', function () {
    Storage::fake('public');

    $attachment = Attachment::fromContent('tarball-bytes', 'backup.tar.gz', 'public', 'archives');

    expect($attachment->extension())->toBe('tar.gz')
        ->and($attachment->mime())->toBe('application/x-compressed-tar')
        ->and($attachment->type())->toBe('archive')
        ->and($attachment->path())->toEndWith('.tar.gz');
});
