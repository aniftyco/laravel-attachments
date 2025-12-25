# File Validation

Laravel Attachments provides built-in file validation to ensure uploaded files meet your requirements before they're stored.

## Basic Validation

### Inline Validation

Validate files when creating attachments:

```php
use NiftyCo\Attachments\Attachment;

$user->avatar = Attachment::fromFile(
    $request->file('avatar'),
    folder: 'avatars',
    validate: ['image', 'max:2048', 'mimes:jpg,png']
);
$user->save();
```

### Array Format

```php
$attachment = Attachment::fromFile(
    $file,
    validate: [
        'image',
        'max:2048',
        'mimes:jpg,png,gif',
        'dimensions:min_width=100,min_height=100'
    ]
);
```

### String Format

```php
$attachment = Attachment::fromFile(
    $file,
    validate: 'image|max:2048|mimes:jpg,png,gif'
);
```

## Common Validation Rules

### File Type Rules

```php
// Must be a file
validate: ['file']

// Must be an image
validate: ['image']

// Specific MIME types
validate: ['mimes:jpg,png,gif,webp']

// Specific MIME type strings
validate: ['mimetypes:image/jpeg,image/png,application/pdf']
```

### File Size Rules

```php
// Maximum size (in kilobytes)
validate: ['max:2048'] // 2MB

// Minimum size (in kilobytes)
validate: ['min:100'] // 100KB

// Combined
validate: ['min:100', 'max:5120'] // Between 100KB and 5MB
```

### Image Dimension Rules

```php
// Minimum dimensions
validate: ['dimensions:min_width=100,min_height=100']

// Maximum dimensions
validate: ['dimensions:max_width=2000,max_height=2000']

// Exact dimensions
validate: ['dimensions:width=800,height=600']

// Aspect ratio
validate: ['dimensions:ratio=16/9']

// Combined
validate: [
    'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
]
```

## Global Validation

Set default validation rules in the configuration file:

```php
// config/attachments.php
return [
    'validation' => [
        'file',
        'max:10240', // 10MB
        'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,zip,rar',
    ],
];
```

These rules apply to all attachments unless overridden.

### Overriding Global Rules

```php
// Override with custom rules
$attachment = Attachment::fromFile(
    $file,
    validate: ['image', 'max:5120']
);

// Disable validation
$attachment = Attachment::fromFile(
    $file,
    validate: null
);
```

## Validation for Multiple Attachments

```php
use NiftyCo\Attachments\Attachments;

$post->images = Attachments::fromFiles(
    $request->file('images'),
    folder: 'posts',
    validate: ['image', 'max:5120', 'mimes:jpg,png']
);
```

Each file in the array is validated individually.

## Handling Validation Errors

Validation errors throw a `ValidationException`:

```php
use NiftyCo\Attachments\Attachment;
use NiftyCo\Attachments\Exceptions\ValidationException;

try {
    $user->avatar = Attachment::fromFile(
        $request->file('avatar'),
        folder: 'avatars',
        validate: ['image', 'max:2048']
    );
    $user->save();
} catch (ValidationException $e) {
    // Handle validation error
    return back()->withErrors(['avatar' => $e->getMessage()]);
}
```

## Request Validation

For better user experience, validate in your form request:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => [
                'nullable',
                'file',
                'image',
                'max:2048',
                'mimes:jpg,png',
                'dimensions:min_width=100,min_height=100'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.max' => 'Avatar must be less than 2MB.',
            'avatar.dimensions' => 'Avatar must be at least 100x100 pixels.',
        ];
    }
}
```

Then create the attachment without additional validation:

```php
public function update(UpdateProfileRequest $request)
{
    $user->avatar = Attachment::fromFile(
        $request->file('avatar'),
        folder: 'avatars'
    );
    $user->save();
}
```

## Custom Validation Rules

You can use Laravel's custom validation rules:

```php
use Illuminate\Validation\Rules\File;

$attachment = Attachment::fromFile(
    $file,
    validate: [
        File::image()
            ->min(100)
            ->max(2048)
            ->dimensions(Rule::dimensions()->minWidth(100)->minHeight(100))
    ]
);
```

## Validation Rule Reference

### File Rules

| Rule | Description | Example |
|------|-------------|---------|
| `file` | Must be a successfully uploaded file | `'file'` |
| `image` | Must be an image (jpeg, png, bmp, gif, svg, webp) | `'image'` |
| `mimes:ext1,ext2` | File extension must match | `'mimes:jpg,png,pdf'` |
| `mimetypes:type1,type2` | MIME type must match | `'mimetypes:image/jpeg,image/png'` |

### Size Rules

| Rule | Description | Example |
|------|-------------|---------|
| `max:value` | Maximum size in kilobytes | `'max:2048'` (2MB) |
| `min:value` | Minimum size in kilobytes | `'min:100'` (100KB) |

### Image Dimension Rules

| Rule | Description | Example |
|------|-------------|---------|
| `dimensions:min_width=X` | Minimum width in pixels | `'dimensions:min_width=100'` |
| `dimensions:max_width=X` | Maximum width in pixels | `'dimensions:max_width=2000'` |
| `dimensions:min_height=X` | Minimum height in pixels | `'dimensions:min_height=100'` |
| `dimensions:max_height=X` | Maximum height in pixels | `'dimensions:max_height=2000'` |
| `dimensions:width=X` | Exact width in pixels | `'dimensions:width=800'` |
| `dimensions:height=X` | Exact height in pixels | `'dimensions:height=600'` |
| `dimensions:ratio=X/Y` | Aspect ratio | `'dimensions:ratio=16/9'` |

## Next Steps

- Learn about [Storage & Disks](storage.md)
- Configure [Automatic Cleanup](cleanup.md)
- Explore [Metadata](metadata.md)

