# 🚀 Quick Start Guide

Get up and running with the Visual Designer in 5 minutes!

## Step 1: Install (2 minutes)

```bash
cd /path/to/your/laravel/app

# Update composer
composer update badrshs/laravel-dynamic-image-composer

# Publish everything
php artisan vendor:publish --tag=dynamic-image-composer-config
php artisan vendor:publish --tag=dynamic-image-composer-migrations
php artisan vendor:publish --tag=dynamic-image-composer-views

# Run migrations
php artisan migrate

# Link storage (if not already done)
php artisan storage:link
```

## Step 2: Create Template (1 minute)

**Option A: Using Tinker**
```bash
php artisan tinker
```

```php
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Illuminate\Support\Facades\Storage;

// Upload a background image first (any certificate template)
Storage::disk('public')->put(
    'templates/my-template.png',
    file_get_contents('/path/to/your/template.png')
);

// Create template
$template = ImageTemplate::create([
    'name' => 'My First Certificate',
    'background_image' => 'templates/my-template.png',
    'width' => 2480,    // Your template width
    'height' => 3508,   // Your template height
    'is_active' => true,
]);

echo "✅ Template created! ID: " . $template->id . "\n";
echo "🎨 Designer: " . url("/image-template/{$template->id}/designer") . "\n";
```

**Option B: Using Filament**
1. Go to admin panel
2. Navigate to "Image Templates"
3. Click "Create"
4. Fill form and upload background

## Step 3: Design Template (2 minutes)

Visit: `/image-template/{id}/designer`

### A. Add Image Elements
1. Click "Choose Image" in sidebar
2. Select a logo/stamp/decoration
3. Click "Upload"
4. Drag the element on canvas
5. Resize using corner handle
6. Adjust properties in sidebar (opacity, z-index)

### B. Add Text Fields
1. Click "+ Add" in Text Fields section
2. Enter field label (e.g., "name", "course")
3. Drag the blue marker on canvas
4. Configure font size, color, alignment
5. Repeat for all fields

### C. Preview & Save
1. Click "Preview" to see result
2. Adjustments auto-save
3. Click "Generate" to create final template

## Step 4: Generate Images (30 seconds)

### Quick Test Route
Add to `routes/web.php`:

```php
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Badrshs\DynamicImageComposer\Services\TemplateImageService;

Route::get('/quick-test', function (TemplateImageService $service) {
    $template = ImageTemplate::first();
    
    return $service->generateAndOutput($template, [
        'name' => 'John Doe',
        'course' => 'Laravel Development',
        'date' => date('F d, Y'),
        'code' => 'CERT-2024-001',
    ], 'test-certificate.png');
});
```

Visit: `/quick-test`

You should see a generated certificate! 🎉

## Step 5: Integrate with Your App

### Update Your Controller

**Before:**
```php
use App\Services\CertificationImageGeneratorService;

public function show($code) {
    $cert = Certification::where('code', $code)->firstOrFail();
    $service = new CertificationImageGeneratorService(...);
    return $service->generateCertificate([...], 'default');
}
```

**After:**
```php
use Badrshs\DynamicImageComposer\Services\TemplateImageService;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;

public function show($code, TemplateImageService $service) {
    $cert = Certification::where('code', $code)->firstOrFail();
    $template = ImageTemplate::where('is_active', true)->first();
    
    return $service->generateAndOutput($template, [
        'name' => $cert->fullName,
        'course' => $cert->course->title,
        'date' => $cert->date->format('F d, Y'),
        'code' => $cert->code,
    ], "certificate-{$code}.png");
}
```

Done! ✨

## Common Tasks

### Generate Multiple Images
```php
use Badrshs\DynamicImageComposer\Services\TemplateImageService;

$service = app(TemplateImageService::class);
$template = ImageTemplate::first();

$certifications->each(function ($cert) use ($service, $template) {
    $service->generateAndSave($template, [
        'name' => $cert->fullName,
        'course' => $cert->course->title,
        'date' => $cert->date->format('Y-m-d'),
    ], "cert-{$cert->id}.png");
});
```

### Display Generated Images
```blade
<x-dynamic-image-composer::generated-images-grid 
    :images="$images"
    itemLabel="Certificate"
/>
```

### Customize Fonts
Edit `config/dynamic-image-composer.php`:
```php
'fonts' => [
    'my-font' => [
        'en' => 'MyFont-Regular.ttf',
        'ar' => 'MyFont-Arabic.ttf',
    ],
],
```

Put fonts in `public/fonts/`

## Troubleshooting

### Routes not found?
```bash
php artisan route:clear
php artisan route:cache
```

### Views not loading?
```bash
php artisan view:clear
```

### Images not showing in designer?
```bash
php artisan storage:link
chmod -R 775 storage/
```

### Fonts not working?
- Check fonts are in `public/fonts/`
- Verify font names in config
- Restart server

## Next Steps

1. ✅ Test the designer
2. ✅ Create production templates
3. ✅ Configure your fonts/colors
4. ✅ Update existing controllers
5. ✅ Test with real data
6. ✅ Deploy!

## Need More Help?

- **Full Documentation:** `README.md`
- **Migration Guide:** `INTEGRATION.md`
- **Architecture:** `PACKAGE-STRUCTURE.md`
- **Testing Guide:** `TESTING.md`
- **Code Examples:** `examples/`

---

**That's it! You now have a visual certificate designer in your Laravel app!** 🎨✨
