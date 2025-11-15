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
php artisan vendor:publish --tag=dynamic-image-composer-views
php artisan migrate
```

### 3. Configure your fonts and colors

Edit `config/dynamic-image-composer.php`:

```php
'fonts' => [
    'default' => [
        'en' => 'Museo500-Regular.ttf',
        'ar' => 'sky.ttf',
    ],
    'monotype' => [
        'en' => 'monotype.ttf',
        'ar' => 'sky.ttf',
    ],
    // Add your custom fonts...
],

'colors' => [
    'black' => [40, 40, 40],
    'brown' => [170, 135, 77],
    'gold' => [212, 175, 55],
    // Add your custom colors...
],
```

### 4. The Visual Designer (Core Feature!)

The package includes a complete **drag-and-drop designer interface**. This is the core of the package:

**Access the designer:**
- Via Filament: Click the "Designer" button on any template
- Direct URL: `/image-template/{templateId}/designer`

**Features:**
- Drag and drop image elements
- Position text fields visually
- Live preview generation
- Real-time configuration updates
- Upload new elements directly from the interface

### 5. Migrating from Your Existing CertificationService

**Before (Old Code):**
```php
use App\Services\CertificationImageGeneratorService;
use App\Services\AdvancedCertificationService;

// Your old service with hardcoded configurations
$service = new CertificationImageGeneratorService($decodeService);
$image = $service->generateCertificate($data, 'default', $customConfig);
```

**After (New Package):**
```php
use Badrshs\DynamicImageComposer\Services\TemplateImageService;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;

// Use template from database
$template = ImageTemplate::where('is_active', true)->first();

$service = app(TemplateImageService::class);
$image = $service->generateAndOutput($template, [
    'name' => $certification->fullName,
    'course' => $certification->course->title,
    'date' => $certification->date->format('Y-m-d'),
    'code' => $certification->code,
], 'certificate.png');

return $image; // Returns HTTP response
```

### 4. Using with Existing Models

You can keep using your `Certification` model and just change the image generation:

```php
use Badrshs\DynamicImageComposer\Services\TemplateImageService;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;

class CertificationController extends Controller
{
    public function showCertificate(string $code, TemplateImageService $service)
    {
        $certification = Certification::where('code', $code)->firstOrFail();
        
        // Get the template (or use a default one)
        $template = ImageTemplate::find($certification->template_id) 
            ?? ImageTemplate::where('is_active', true)->first();
        
        return $service->generateAndOutput($template, [
            'name' => $certification->firstName . ' ' . $certification->surname,
            'course' => $certification->course->title,
            'date' => $certification->date->format('F d, Y'),
            'code' => $certification->code,
        ], "certificate-{$code}.png");
    }
    
    public function generateForGroup(int $groupId, TemplateImageService $service)
    {
        $certifications = Certification::where('group_id', $groupId)->get();
        $template = ImageTemplate::where('is_active', true)->first();
        
        $generatedImages = [];
        
        foreach ($certifications as $cert) {
            $result = $service->generateAndSave($template, [
                'name' => $cert->fullName,
                'course' => $cert->course->title,
                'date' => $cert->date->format('F d, Y'),
                'code' => $cert->code,
            ], "cert-{$cert->code}.png");
            
            $generatedImages[] = [
                'url' => $result['url'],
                'name' => $cert->fullName,
                'filename' => $result['filename'],
                'metadata' => $cert->date->format('Y-m-d'),
            ];
        }
        
        // Display using the component
        return view('certifications.batch', compact('generatedImages'));
    }
}
```

### 5. Displaying Generated Images

In your Blade views, use the included component:

```blade
{{-- resources/views/certifications/batch.blade.php --}}
<x-dynamic-image-composer::generated-images-grid 
    :images="$generatedImages"
    itemLabel="Certificate"
/>
```

Or create your own custom view based on the component.

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

This gives you:
- Full template CRUD
- Visual designer interface (the core feature!)
- Element management
- Live preview

### 6. Route Examples

The package automatically registers routes for the designer:

- `/image-template/{id}/designer` - Visual designer interface
- `/image-template/{id}/preview` - Live preview generation
- `/image-template/{id}/elements` - Upload elements
- `/image-template/{id}/save-configuration` - Save layout
- `/image-template/{id}/generate` - Generate final image

You can use these in your own controllers or Filament actions.

### 7. Migration Checklist

- [ ] Install package via composer
- [ ] Publish config, migrations, and views
- [ ] Run migrations
- [ ] Copy fonts to `public/fonts/`
- [ ] Configure fonts and colors in config
- [ ] Create templates via Filament or database
- [ ] Use the visual designer to layout elements
- [ ] Update controllers to use `TemplateImageService`
- [ ] Replace old service calls with new package
- [ ] Test generation with sample data
- [ ] Update routes if using custom URLs

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
