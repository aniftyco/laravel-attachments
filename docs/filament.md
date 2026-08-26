# Filament Integration

Laravel Attachments provides ready-to-use Filament components for forms and tables.

## Installation

Filament is an optional dependency. Install it if you haven't already:

```bash
composer require filament/filament
```

## Form Fields

### AttachmentField

Use `AttachmentField` for file uploads in Filament forms:

```php
use NiftyCo\Attachments\Filament\AttachmentField;

public static function form(Form $form): Form
{
    return $form->schema([
        AttachmentField::make('avatar')
            ->label('Profile Picture')
            ->disk('public')
            ->directory('avatars')
            ->required(),
    ]);
}
```

### Image Uploads

Configure for image uploads:

```php
AttachmentField::make('avatar')
    ->label('Avatar')
    ->images()
    ->maxSize(2048) // 2MB
    ->acceptedFileTypes(['image/jpeg', 'image/png'])
    ->disk('public')
    ->directory('avatars');
```

### Document Uploads

Configure for document uploads:

```php
AttachmentField::make('document')
    ->label('Document')
    ->acceptedFileTypes(['application/pdf', 'application/msword'])
    ->maxSize(10240) // 10MB
    ->disk('local')
    ->directory('documents');
```

### Multiple Files

Enable multiple file uploads:

```php
AttachmentField::make('images')
    ->label('Gallery Images')
    ->multiple()
    ->images()
    ->maxFiles(10)
    ->disk('public')
    ->directory('gallery');
```

### Field Options

```php
AttachmentField::make('avatar')
    // Storage
    ->disk('s3')
    ->directory('user-avatars')
    
    // Validation
    ->required()
    ->maxSize(2048) // KB
    ->minSize(100)  // KB
    ->acceptedFileTypes(['image/jpeg', 'image/png'])
    
    // Image specific
    ->images()
    ->imagePreviewHeight(200)
    ->imageCropAspectRatio('1:1')
    ->imageResizeTargetWidth(800)
    ->imageResizeTargetHeight(800)
    
    // Multiple files
    ->multiple()
    ->maxFiles(5)
    ->minFiles(1)
    
    // UI
    ->label('Profile Picture')
    ->helperText('Upload a profile picture (max 2MB)')
    ->columnSpan('full');
```

## Table Columns

### AttachmentColumn

`AttachmentColumn` extends Filament's `TextColumn`. For a single-attachment attribute it shows the file's human-readable size, prefixes a file-type icon (image, PDF, video, audio, document, or a generic paper clip), and links to the file, opening it in a new tab when clicked:

```php
use NiftyCo\Attachments\Filament\AttachmentColumn;

public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('name'),
        AttachmentColumn::make('avatar')
            ->label('Avatar'),
    ]);
}
```

The icon and link are configured for you by `make()`. Because it is a `TextColumn`, the rest of Filament's text-column API is available:

```php
AttachmentColumn::make('avatar')
    ->label('Profile Picture')
    ->alignCenter()
    ->toggleable();
```

## Complete Resource Example

```php
namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NiftyCo\Attachments\Filament\AttachmentField;
use NiftyCo\Attachments\Filament\AttachmentColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required(),
            
            Forms\Components\TextInput::make('email')
                ->email()
                ->required(),
            
            AttachmentField::make('avatar')
                ->label('Profile Picture')
                ->images()
                ->maxSize(2048)
                ->disk('public')
                ->directory('avatars')
                ->imagePreviewHeight(200)
                ->imageCropAspectRatio('1:1'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable(),
            
            Tables\Columns\TextColumn::make('email')
                ->searchable(),
            
            AttachmentColumn::make('avatar')
                ->label('Avatar'),
        ]);
    }
}
```

## Gallery Resource Example

```php
namespace App\Filament\Resources;

use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NiftyCo\Attachments\Filament\AttachmentField;
use NiftyCo\Attachments\Filament\AttachmentColumn;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required(),
            
            Forms\Components\Textarea::make('content')
                ->required(),
            
            AttachmentField::make('images')
                ->label('Gallery Images')
                ->multiple()
                ->images()
                ->maxFiles(10)
                ->maxSize(5120)
                ->disk('public')
                ->directory('posts')
                ->imagePreviewHeight(150)
                ->columnSpan('full'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable(),
            
            Tables\Columns\TextColumn::make('images')
                ->label('Images')
                ->formatStateUsing(fn ($state) => $state instanceof \NiftyCo\Attachments\Attachments ? $state->count().' files' : null),
        ]);
    }
}
```

`AttachmentColumn` renders a single attachment. For a collection attribute, use a plain `TextColumn` as above, or loop over the attachments in a custom view.

## Validation

Filament field validation:

```php
AttachmentField::make('avatar')
    ->required()
    ->rules([
        'required',
        'image',
        'max:2048',
        'dimensions:min_width=100,min_height=100',
    ]);
```

## Custom Validation Messages

```php
AttachmentField::make('avatar')
    ->required()
    ->maxSize(2048)
    ->validationMessages([
        'required' => 'Please upload a profile picture.',
        'max' => 'The image must not be larger than 2MB.',
    ]);
```

## Next Steps

- Learn about [Testing](testing.md)
- Explore [API Resources](api-resources.md)
- Configure [Events](events.md)

