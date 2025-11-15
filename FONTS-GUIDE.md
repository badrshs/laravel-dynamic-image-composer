# Font Setup Guide

## The Font Issue

The error `imagettfbbox(): Could not find/open font` means the package can't find the font files.

## Quick Fix - Option 1: Use the Install Command

```bash
# In your Laravel app
composer update badrshs/laravel-dynamic-image-composer

# Run install with fonts
php artisan dynamic-image-composer:install --with-fonts
```

This will publish default fonts to `storage/app/public/fonts/`

## Quick Fix - Option 2: Add Your Own Fonts

1. **Create the fonts directory:**
```bash
mkdir -p storage/app/public/fonts
```

2. **Download free fonts:**

**For English** - Roboto (Google Font):
- Visit: https://fonts.google.com/specimen/Roboto
- Download and extract
- Copy `Roboto-Regular.ttf` to `storage/app/public/fonts/`

**For Arabic** - Noto Kufi Arabic:
- Visit: https://fonts.google.com/specimen/Noto+Kufi+Arabic
- Download and extract  
- Copy `NotoKufiArabic[wght].ttf` (rename to `NotoKufiArabic-Regular.ttf`)
- Place in `storage/app/public/fonts/`

3. **Or use any .ttf font you have:**
```bash
# Copy your fonts
cp /path/to/your/font.ttf storage/app/public/fonts/
```

4. **Update config** (`config/dynamic-image-composer.php`):
```php
'fonts' => [
    'default' => [
        'en' => 'YourFont-Regular.ttf',  // Your English font
        'ar' => 'YourArabicFont-Regular.ttf',  // Your Arabic font
    ],
],
```

## Verify Fonts Are Working

```bash
# Check fonts directory
ls -la storage/app/public/fonts/

# Should show:
# Roboto-Regular.ttf
# NotoKufiArabic-Regular.ttf
# (or your custom fonts)
```

## Alternative: Use Public Fonts Directory

If you prefer using `public/fonts/`:

1. **Update config:**
```php
// config/dynamic-image-composer.php
'fonts_storage' => 'public',  // Changed from 'storage'
```

2. **Create directory and add fonts:**
```bash
mkdir -p public/fonts
# Copy fonts to public/fonts/
```

## Test It

Create a test route:

```php
Route::get('/test-fonts', function () {
    $composer = app(\Badrshs\DynamicImageComposer\DynamicImageComposer::class);
    
    $image = $composer->generate('templates/your-template.png', [
        'test' => [
            'value' => 'Hello World',
            'x' => 100,
            'y' => 100,
            'fontSize' => 40,
            'color' => 'black',
            'font' => 'default',
        ]
    ]);
    
    return $composer->output($image, 'test.png');
});
```

Visit `/test-fonts` - if you see text, fonts are working! 🎉

## Common Issues

**"Could not find/open font"**
- Check file exists: `ls storage/app/public/fonts/Roboto-Regular.ttf`
- Check permissions: `chmod 644 storage/app/public/fonts/*.ttf`
- Check storage link: `php artisan storage:link`

**Fonts don't load from storage**
- Try switching to public: Set `'fonts_storage' => 'public'` in config
- Move fonts to `public/fonts/` directory

**Wrong font showing**
- Clear config cache: `php artisan config:clear`
- Check font name matches config exactly
- Font filename is case-sensitive!

## Need Help?

Check `resources/fonts/DOWNLOAD.md` in the package for download links.
