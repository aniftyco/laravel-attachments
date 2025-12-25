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
            ->folder('avatars')
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
    ->folder('avatars');
```

### Document Uploads

Configure for document uploads:

```php
AttachmentField::make('document')
    ->label('Document')
    ->acceptedFileTypes(['application/pdf', 'application/msword'])
    ->maxSize(10240) // 10MB
    ->disk('local')
    ->folder('documents');
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
    ->folder('gallery');
```

### Field Options

```php
AttachmentField::make('avatar')
    // Storage
    ->disk('s3')
    ->folder('user-avatars')
    
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

Display attachments in Filament tables:

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

### Image Columns

Display images with preview:

```php
AttachmentColumn::make('avatar')
    ->label('Avatar')
    ->circular()
    ->size(40);
```

### Column Options

```php
AttachmentColumn::make('avatar')
    // Display
    ->circular()
    ->square()
    ->size(60)
    ->height(80)
    ->width(80)
    
    // Behavior
    ->openUrlInNewTab()
    ->defaultImageUrl('/images/default-avatar.png')
    
    // UI
    ->label('Profile Picture')
    ->alignCenter();
```

### Multiple Images Column

Display multiple images:

```php
AttachmentColumn::make('images')
    ->label('Gallery')
    ->limit(3)
    ->ring(2)
    ->overlap(4);
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
                ->folder('avatars')
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
                ->label('Avatar')
                ->circular()
                ->size(40),
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
                ->folder('posts')
                ->imagePreviewHeight(150)
                ->columnSpan('full'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable(),
            
            AttachmentColumn::make('images')
                ->label('Images')
                ->limit(3)
                ->size(40),
        ]);
    }
}
```

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

