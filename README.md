# Laravel Dynamic Image Composer

Compose images dynamically from templates with text and image overlays. Perfect for generating certificates, badges, social media graphics, or any dynamic image content.

## Features

- 🎨 **Visual Designer Interface** - Drag-and-drop template designer with live preview
- 📝 Dynamic text overlays with custom fonts, colors, and positioning
- 🖼️ Image overlay support with opacity and positioning
- 🌍 Multi-language support (Arabic, English, and more)
- 📐 Flexible positioning (left, center, right alignment)
- 🎯 Template-based generation with database storage
- 🔧 Filament admin panel integration (optional)
- 💾 Multiple storage disk support
- ⚡ Simple, fluent API

## Installation

Install via Composer:

```bash
composer require badrshs/laravel-dynamic-image-composer
```

Publish configuration and migrations:

```bash
php artisan vendor:publish --tag=dynamic-image-composer-config
php artisan vendor:publish --tag=dynamic-image-composer-migrations
php artisan vendor:publish --tag=dynamic-image-composer-views
php artisan migrate
```

## Quick Start

### Visual Designer Interface

The package includes a complete **drag-and-drop visual designer** for creating and editing templates:

1. Create a template via Filament or directly in the database
2. Click the **"Designer"** button on any template
3. Upload image elements (logos, stamps, decorations)
4. Drag and position elements on the canvas
5. Add text fields with positioning and styling
6. Preview your design in real-time
7. Generate the final composite image

**Accessing the Designer:**

```php
// From Filament: Click "Designer" button on any template
// Or directly via route:
route('image-template.designer', ['template' => $templateId])
```

### Basic Usage

```php
use Badrshs\DynamicImageComposer\DynamicImageComposer;

$composer = new DynamicImageComposer();

// Generate image with text overlays
$image = $composer->generate(
    templatePath: 'templates/my-template.png',
    fields: [
        'name' => [
            'value' => 'John Doe',
            'x' => 'center',
            'y' => 500,
            'fontSize' => 60,
            'color' => 'black',
            'font' => 'default',
            'alignment' => 'center'
        ],
        'date' => [
            'value' => date('Y-m-d'),
            'x' => 100,
            'y' => 1000,
            'fontSize' => 30,
            'color' => 'gray',
            'font' => 'default'
        ]
    ]
);

// Save to storage
$result = $composer->save($image, 'output-' . time() . '.png');
// Returns: ['path' => '...', 'url' => '...', 'filename' => '...', 'disk' => '...']

// Or output directly as HTTP response
return $composer->output($image, 'my-image.png');
```

### With Image Overlays

```php
$image = $composer->generate('templates/base.png', [
    'title' => [
        'value' => 'Certificate of Achievement',
        'x' => 'center',
        'y' => 300,
        'fontSize' => 80,
        'color' => 'gold'
    ]
]);

// Add logo overlay
$composer->addOverlay($image, 'logos/company-logo.png', [
    'x' => 100,
    'y' => 100,
    'width' => 200,
    'height' => 200,
    'opacity' => 0.8
]);

return $composer->output($image);
```

### Using Database Templates

```php
use Badrshs\DynamicImageComposer\Models\ImageTemplate;

// Create a template
$template = ImageTemplate::create([
    'name' => 'My Certificate Template',
    'background_image' => 'templates/certificate-bg.png',
    'width' => 2480,
    'height' => 3508,
    'is_active' => true,
    'field_configuration' => [
        'fields' => [
            'name' => [
                'x' => 'center',
                'y' => 1200,
                'fontSize' => 100,
                'color' => 'black',
                'font' => 'monotype',
                'alignment' => 'center'
            ]
        ]
    ]
]);

// Generate from template
$composer = new DynamicImageComposer();
$image = $composer->generate(
    $template->background_image,
    array_merge(
        ['name' => ['value' => 'Jane Smith']],
        $template->field_configuration['fields'] ?? []
    )
);
```

## Configuration

Edit `config/dynamic-image-composer.php`:

```php
return [
    // Storage disk for templates and generated images
    'disk' => env('DYNAMIC_IMAGE_DISK', 'public'),
    
    // Directories
    'templates_directory' => 'image-templates',
    'elements_directory' => 'image-elements',
    'generated_directory' => 'generated-images',
    'fonts_directory' => 'fonts',
    
    // Font definitions (add your custom fonts)
    'fonts' => [
        'default' => [
            'en' => 'Museo500-Regular.ttf',
            'ar' => 'sky.ttf',
        ],
        // Add more fonts...
    ],
    
    // Color definitions (RGB values)
    'colors' => [
        'black' => [40, 40, 40],
        'white' => [255, 255, 255],
        'gold' => [212, 175, 55],
        // Add more colors...
    ],
];
```

## Field Configuration

Each field supports these options:

| Option | Type | Description | Default |
|--------|------|-------------|---------|
| `value` | string | Text to display | Required |
| `x` | int\|'center' | X position or 'center' | 0 |
| `y` | int | Y position | 0 |
| `fontSize` | int | Font size | 20 |
| `color` | string | Color name from config | 'black' |
| `font` | string | Font name from config | 'default' |
| `alignment` | string | 'left', 'center', 'right' | 'left' |

## Filament Integration (Optional)

If you're using Filament, register the resource in your panel:

```php
use Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource;

public function panel(Panel $panel): Panel
{
    return $panel
        ->resources([
            ImageTemplateResource::class,
        ]);
}
```

This provides a full admin interface for managing templates and elements, including:
- Template CRUD operations
- Visual designer interface
- Element management
- Live preview generation

## Displaying Generated Images

Use the included Blade component to display generated images in a grid:

```blade
<x-dynamic-image-composer::generated-images-grid 
    :images="$generatedImages"
    itemLabel="Certificate"
/>
```

Where `$generatedImages` is an array of:
```php
[
    [
        'url' => 'https://...',
        'name' => 'John Doe',
        'filename' => 'certificate.png',
        'metadata' => 'Generated on 2024-01-01' // optional
    ],
    // ...
]
```

## Advanced Usage

### Custom Fonts

Add custom fonts to your `public/fonts` directory or storage, then register them in config:

```php
'fonts' => [
    'my-font' => [
        'en' => 'MyFont-Regular.ttf',
        'ar' => 'MyFont-Arabic.ttf',
    ],
],
```

### Custom Colors

Add custom colors in config:

```php
'colors' => [
    'brand-blue' => [30, 144, 255],
    'brand-red' => [220, 53, 69],
],
```

### Arabic Text Support

The package automatically detects and handles Arabic text:

```php
$image = $composer->generate('template.png', [
    'name' => [
        'value' => 'محمد أحمد', // Arabic text
        'x' => 'center',
        'y' => 500,
        'fontSize' => 60,
        'font' => 'default', // Will use Arabic font variant
    ]
]);
```

## Use Cases

- 📜 Certificates and diplomas
- 🏆 Achievement badges
- 🎫 Event tickets
- 📱 Social media graphics
- 🎴 ID cards and passes
- 📊 Dynamic reports with charts
- 🎨 Personalized marketing materials

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- GD Library (enabled by default in most PHP installations)

## License

MIT License. See LICENSE file for details.

## Credits

Developed by Badr Shs
