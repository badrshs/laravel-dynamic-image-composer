# Testing the Package

## Quick Test Checklist

### 1. Install & Publish
```bash
cd /path/to/your/main/app
composer update badrshs/laravel-dynamic-image-composer
php artisan vendor:publish --tag=dynamic-image-composer-config
php artisan vendor:publish --tag=dynamic-image-composer-migrations
php artisan vendor:publish --tag=dynamic-image-composer-views
php artisan migrate
```

### 2. Create a Test Template

**Option A: Via Tinker**
```bash
php artisan tinker
```

```php
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Illuminate\Support\Facades\Storage;

// Make sure you have a background image in storage
Storage::disk('public')->put('test-template.png', file_get_contents('path/to/your/bg.png'));

$template = ImageTemplate::create([
    'name' => 'Test Certificate Template',
    'description' => 'Testing the visual designer',
    'background_image' => 'test-template.png',
    'width' => 2480,
    'height' => 3508,
    'is_active' => true,
]);

echo "Template created with ID: " . $template->id;
echo "\nDesigner URL: " . route('image-template.designer', ['template' => $template->id]);
```

**Option B: Via Filament**
1. Go to your admin panel
2. Navigate to Image Templates
3. Create New Template
4. Upload a background image

### 3. Test the Visual Designer

Visit: `/image-template/{id}/designer`

**Test these features:**
- [ ] Can you see the canvas with the background image?
- [ ] Upload an element (logo/stamp image)
- [ ] Drag the element around
- [ ] Resize the element using the handle
- [ ] Add a text field
- [ ] Position the text field marker
- [ ] Adjust text field properties (font size, color, etc.)
- [ ] Click "Preview" - does it generate?
- [ ] Click "Generate" - does it create the final image?

### 4. Test Programmatic Generation

Create a test route in `routes/web.php`:

```php
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Badrshs\DynamicImageComposer\Services\TemplateImageService;

Route::get('/test-certificate', function (TemplateImageService $service) {
    $template = ImageTemplate::where('is_active', true)->first();
    
    if (!$template) {
        return 'No active template found. Create one first!';
    }
    
    return $service->generateAndOutput($template, [
        'name' => 'John Doe',
        'course' => 'Laravel Development',
        'date' => date('F d, Y'),
        'code' => 'TEST-2024-001',
    ], 'test-certificate.png');
});
```

Visit `/test-certificate` and see if the image generates.

### 5. Test the Grid Component

Create a test view:

```blade
{{-- resources/views/test-grid.blade.php --}}
<x-dynamic-image-composer::generated-images-grid 
    :images="[
        [
            'url' => asset('storage/test-image.png'),
            'name' => 'John Doe',
            'filename' => 'certificate-001.png',
            'metadata' => 'Generated: ' . date('Y-m-d'),
        ],
        [
            'url' => asset('storage/test-image.png'),
            'name' => 'Jane Smith',
            'filename' => 'certificate-002.png',
        ],
    ]"
    itemLabel="Certificate"
/>
```

Route:
```php
Route::get('/test-grid', function () {
    return view('test-grid');
});
```

### 6. Test with Your Existing Certifications

Update your `CertificationController`:

```php
use Badrshs\DynamicImageComposer\Services\TemplateImageService;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;

public function showCertificate(string $code, TemplateImageService $service)
{
    $certification = Certification::where('code', $code)->firstOrFail();
    
    $template = ImageTemplate::where('is_active', true)->first();
    
    if (!$template) {
        abort(500, 'No certificate template configured');
    }
    
    return $service->generateAndOutput($template, [
        'name' => $certification->firstName . ' ' . $certification->surname,
        'course' => $certification->course->title ?? 'Course',
        'date' => $certification->date->format('F d, Y'),
        'code' => $certification->code,
    ], "certificate-{$code}.png");
}
```

### 7. Common Issues & Solutions

**Issue: Routes not found**
```bash
php artisan route:clear
php artisan route:cache
```

**Issue: Views not found**
```bash
php artisan view:clear
```

**Issue: Fonts not loading**
- Check fonts are in `public/fonts/`
- Check font names in `config/dynamic-image-composer.php`
- Make sure font files have correct permissions

**Issue: Images not displaying in designer**
```bash
php artisan storage:link
```

**Issue: Permission errors**
```bash
chmod -R 775 storage/
chmod -R 775 public/storage/
```

### 8. Performance Test

Generate 100 certificates:

```php
Route::get('/test-batch', function (TemplateImageService $service) {
    $template = ImageTemplate::first();
    $start = microtime(true);
    
    for ($i = 1; $i <= 100; $i++) {
        $result = $service->generateAndSave($template, [
            'name' => "Student $i",
            'course' => 'Test Course',
            'date' => date('Y-m-d'),
            'code' => "TEST-$i",
        ], "batch-cert-$i.png");
    }
    
    $time = microtime(true) - $start;
    
    return "Generated 100 certificates in " . round($time, 2) . " seconds";
});
```

### 9. Cleanup Test Data

```php
// In tinker or a cleanup route
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Badrshs\DynamicImageComposer\Models\TemplateElement;
use Illuminate\Support\Facades\Storage;

// Delete test templates
$testTemplates = ImageTemplate::where('name', 'like', '%Test%')->get();
foreach ($testTemplates as $template) {
    $template->elements()->delete();
    if ($template->background_image) {
        Storage::disk('public')->delete($template->background_image);
    }
    $template->delete();
}
```

## Success Criteria

✅ Designer interface loads and is interactive
✅ Can upload and position elements
✅ Can configure text fields
✅ Preview generates successfully
✅ Final generation works
✅ Programmatic generation works
✅ Grid component displays images
✅ Integration with existing models works
✅ No errors in logs

## Next Steps After Testing

1. Create production templates
2. Configure proper fonts and colors
3. Set up proper storage disk (S3, etc.)
4. Add authentication/authorization as needed
5. Customize views if needed
6. Deploy!

## Need Help?

Check these files:
- `README.md` - General usage
- `INTEGRATION.md` - Migration guide
- `PACKAGE-STRUCTURE.md` - Architecture overview
- `examples/ImageGenerationExampleController.php` - Code examples
