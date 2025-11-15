# Integration Guide

## Using the Package in Your Main Application

### 1. Add to your main composer.json

Since this is a local package, add it to your root `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/laravel-dynamic-image-composer"
        }
    ],
    "require": {
    "badrshs/laravel-dynamic-image-composer": "@dev"
    }
}
```

Then run:
```bash
composer update badrshs/laravel-dynamic-image-composer
```

### 2. Publish assets

```bash
php artisan vendor:publish --tag=dynamic-image-composer-config
php artisan vendor:publish --tag=dynamic-image-composer-migrations
php artisan migrate
```

### 3. Migrating from CertificationService

Replace your existing certification generation with the new package:

**Before:**
```php
use App\Services\CertificationImageGeneratorService;

$service = new CertificationImageGeneratorService($decodeService);
$image = $service->generateCertificate($data, 'default', $customConfig);
```

**After:**
```php
use Badrshs\DynamicImageComposer\DynamicImageComposer;

$composer = new DynamicImageComposer();
$image = $composer->generate(
    $templatePath,
    [
        'name' => [
            'value' => $data['name'],
            'x' => 'center',
            'y' => 1450,
            'fontSize' => 175,
            'color' => 'brown',
            'alignment' => 'center',
            'font' => 'monotype',
        ],
        // ... other fields
    ]
);
```

### 4. Using with Existing Models

You can keep using your `Certification` model and just change the image generation:

```php
use Badrshs\DynamicImageComposer\Services\TemplateImageService;

class CertificationController extends Controller
{
    public function generateImage(Certification $certification, TemplateImageService $service)
    {
        $template = ImageTemplate::find($certification->template_id);
        
        return $service->generateAndOutput($template, [
            'name' => $certification->firstName . ' ' . $certification->surname,
            'course' => $certification->course->title,
            'date' => $certification->date,
            'code' => $certification->code,
        ], 'certificate.png');
    }
}
```

### 5. Filament Integration (Optional)

Add to your Filament Admin Panel in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other config
        ->resources([
            ImageTemplateResource::class,
            // Your other resources...
        ]);
}
```

### 6. Route Examples

```php
// routes/web.php
use Badrshs\DynamicImageComposer\DynamicImageComposer;

Route::get('/generate-certificate/{certification}', function (Certification $certification) {
    $composer = app(DynamicImageComposer::class);
    
    $image = $composer->generate(
        $certification->template->background_image,
        [
            'name' => [
                'value' => $certification->fullName,
                'x' => 'center',
                'y' => 1200,
                'fontSize' => 100,
                'color' => 'brown',
                'alignment' => 'center',
                'font' => 'monotype',
            ]
        ]
    );
    
    return $composer->output($image, "certificate-{$certification->code}.png");
});
```

## Benefits of the Package

✅ **Generic & Reusable** - Not tied to certificates, works for any image generation
✅ **No Business Logic** - Pure image composition, you control the data
✅ **Multi-language** - Built-in Arabic support
✅ **Database Templates** - Store configurations in database
✅ **Easy Testing** - Clean service layer for unit tests
✅ **Configurable** - Fonts, colors, storage all configurable
✅ **Framework Integrated** - Uses Laravel filesystem, config, logging

## Next Steps

1. Run `composer update` to install the package
2. Publish and run migrations
3. Copy your fonts to `public/fonts/`
4. Update your existing controllers to use the new service
5. Create templates via Filament or directly in database
6. Test generation with example routes
