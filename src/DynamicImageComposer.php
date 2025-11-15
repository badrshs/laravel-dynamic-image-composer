<?php

namespace Badrshs\DynamicImageComposer;

use ArPHP\I18N\Arabic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DynamicImageComposer
{
    protected Arabic $arabic;
    protected array $fonts = [];
    protected array $colors = [];

    public function __construct()
    {
        $this->arabic = new Arabic();
    }

    /**
     * Generate image from template with dynamic text fields
     *
     * @param string $templatePath Path to template image in storage
     * @param array $fields Array of field configurations and values
     * @param array $options Additional options (fonts, colors, etc.)
     * @return resource GD image resource
     */
    public function generate(string $templatePath, array $fields, array $options = [])
    {
        Log::info('Generating dynamic image', ['template' => $templatePath]);

        try {
            // Create base image from template
            $image = $this->createBaseImage($templatePath);

            // Load fonts and colors
            $this->loadFonts($options['fonts'] ?? []);
            $this->loadColors($image, $options['colors'] ?? []);

            // Get image dimensions
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);

            // Add each field to the image
            foreach ($fields as $fieldName => $config) {
                if (!isset($config['value'])) {
                    continue;
                }

                $this->addTextField($image, $config, $imageWidth, $imageHeight);
            }

            Log::info('Dynamic image generated successfully');

            return $image;
        } catch (\Throwable $e) {
            Log::error('Error generating dynamic image', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Save generated image to storage
     *
     * @param resource $image GD image resource
     * @param string $filename Filename to save as
     * @param string|null $disk Storage disk (defaults to config)
     * @return array Path and URL information
     */
    public function save($image, string $filename, ?string $disk = null): array
    {
        $disk = $disk ?? config('dynamic-image-composer.disk');
        $directory = config('dynamic-image-composer.generated_directory');

        // Ensure directory exists
        Storage::disk($disk)->makeDirectory($directory);

        // Generate full path
        $path = $directory . '/' . $filename;
        $fullPath = Storage::disk($disk)->path($path);

        // Save image
        $result = imagepng($image, $fullPath);
        imagedestroy($image);

        if (!$result) {
            throw new \RuntimeException('Failed to save generated image');
        }

        return [
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'filename' => $filename,
            'disk' => $disk,
        ];
    }

    /**
     * Output image as HTTP response
     *
     * @param resource $image GD image resource
     * @param string $filename Suggested filename for download
     * @return \Illuminate\Http\Response
     */
    public function output($image, string $filename = 'image.png')
    {
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Add overlay image to base image
     *
     * @param resource $baseImage Base GD image resource
     * @param string $overlayPath Path to overlay image in storage
     * @param array $config Configuration (x, y, width, height, opacity)
     * @return void
     */
    public function addOverlay($baseImage, string $overlayPath, array $config): void
    {
        $overlayImage = $this->createImageFromPath($overlayPath);

        if (!$overlayImage) {
            Log::warning('Could not load overlay image', ['path' => $overlayPath]);
            return;
        }

        $width = $config['width'] ?? imagesx($overlayImage);
        $height = $config['height'] ?? imagesy($overlayImage);
        $x = $config['x'] ?? 0;
        $y = $config['y'] ?? 0;
        $opacity = $config['opacity'] ?? 1.0;

        // Resize if needed
        if ($width !== imagesx($overlayImage) || $height !== imagesy($overlayImage)) {
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);

            imagecopyresampled(
                $resized,
                $overlayImage,
                0,
                0,
                0,
                0,
                $width,
                $height,
                imagesx($overlayImage),
                imagesy($overlayImage)
            );

            imagedestroy($overlayImage);
            $overlayImage = $resized;
        }

        // Apply opacity
        if ($opacity < 1.0) {
            $this->applyOpacity($overlayImage, $opacity);
        }

        // Copy to base image
        imagecopy($baseImage, $overlayImage, $x, $y, 0, 0, $width, $height);
        imagedestroy($overlayImage);
    }

    /**
     * Create base image from template
     *
     * @param string $templatePath Path in storage
     * @return resource GD image resource
     */
    protected function createBaseImage(string $templatePath)
    {
        $disk = config('dynamic-image-composer.disk');

        if (!Storage::disk($disk)->exists($templatePath)) {
            throw new \RuntimeException("Template image not found: {$templatePath}");
        }

        // Create temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'img_template_');
        file_put_contents($tempPath, Storage::disk($disk)->get($templatePath));

        // Get image info
        $imageInfo = getimagesize($tempPath);
        if (!$imageInfo) {
            unlink($tempPath);
            throw new \RuntimeException('Unable to get image info for template');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // Create blank image
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Load template
        $template = imagecreatefrompng($tempPath);
        imagecopy($image, $template, 0, 0, 0, 0, $width, $height);
        imagedestroy($template);

        unlink($tempPath);

        return $image;
    }

    /**
     * Create image resource from storage path
     */
    protected function createImageFromPath(string $path)
    {
        $disk = config('dynamic-image-composer.disk');

        if (!Storage::disk($disk)->exists($path)) {
            return false;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'img_overlay_');
        file_put_contents($tempPath, Storage::disk($disk)->get($path));

        $imageInfo = getimagesize($tempPath);
        if (!$imageInfo) {
            unlink($tempPath);
            return false;
        }

        $image = match ($imageInfo[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($tempPath),
            IMAGETYPE_JPEG => imagecreatefromjpeg($tempPath),
            IMAGETYPE_GIF => imagecreatefromgif($tempPath),
            default => false,
        };

        unlink($tempPath);

        return $image;
    }

    /**
     * Add text field to image
     */
    protected function addTextField($image, array $config, int $imageWidth, int $imageHeight): void
    {
        $text = (string) $config['value'];
        $isArabic = $this->isArabic($text);

        // Process Arabic text
        if ($isArabic) {
            $text = $this->arabic->utf8Glyphs($text);
        }

        // Get font
        $fontStyle = $config['font'] ?? 'default';
        $langKey = $isArabic ? 'ar' : 'en';
        $font = $this->fonts[$fontStyle][$langKey] ?? $this->getDefaultFont($langKey);

        // Get color
        $colorKey = $config['color'] ?? 'black';
        $color = $this->colors[$colorKey] ?? $this->colors['black'];

        // Get font size
        $fontSize = $config['fontSize'] ?? $config['font_size'] ?? 20;

        // Calculate X position
        $x = $this->calculateXPosition(
            $config['x'] ?? 0,
            $text,
            $fontSize,
            $font,
            $imageWidth,
            $config['alignment'] ?? 'left'
        );

        $y = $config['y'] ?? 0;

        // Add text to image
        imagettftext($image, $fontSize, 0, $x, $y, $color, $font, $text);
    }

    /**
     * Calculate X position based on alignment
     */
    protected function calculateXPosition($x, string $text, int $fontSize, string $font, int $imageWidth, string $alignment): int
    {
        if ($x === 'center' || $alignment === 'center') {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = $box[2] - $box[0];
            return ($imageWidth - $textWidth) / 2;
        }

        if ($alignment === 'right') {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = $box[2] - $box[0];
            return $imageWidth - $textWidth - ($x ?? 0);
        }

        return $x;
    }

    /**
     * Check if text contains Arabic characters
     */
    protected function isArabic(string $text): bool
    {
        return preg_match('/\p{Arabic}/u', $text) === 1;
    }

    /**
     * Load fonts from config and custom definitions
     */
    protected function loadFonts(array $customFonts = []): void
    {
        $configFonts = config('dynamic-image-composer.fonts', []);
        $allFonts = array_merge($configFonts, $customFonts);

        foreach ($allFonts as $style => $languages) {
            foreach ($languages as $lang => $fontFile) {
                $this->fonts[$style][$lang] = $this->getFontPath($fontFile);
            }
        }
    }

    /**
     * Get full font path
     */
    protected function getFontPath(string $fontFile): string
    {
        $fontsStorage = config('dynamic-image-composer.fonts_storage', 'storage');
        $fontsDir = config('dynamic-image-composer.fonts_directory', 'fonts');

        // Determine base path based on storage type
        if ($fontsStorage === 'storage') {
            $basePath = Storage::disk('public')->path($fontsDir);
        } else {
            $basePath = public_path($fontsDir);
        }

        $fontPath = $basePath . DIRECTORY_SEPARATOR . $fontFile;

        // Check if font exists
        if (file_exists($fontPath)) {
            return $fontPath;
        }

        // Try alternative locations
        $alternatives = [
            Storage::disk('public')->path($fontsDir . '/' . $fontFile),
            public_path($fontsDir . '/' . $fontFile),
            storage_path('app/public/fonts/' . $fontFile),
            public_path('fonts/' . $fontFile),
        ];

        foreach ($alternatives as $altPath) {
            if (file_exists($altPath)) {
                return $altPath;
            }
        }

        Log::warning("Font file not found: {$fontFile}", [
            'searched_paths' => array_merge([$fontPath], $alternatives)
        ]);

        // Return the first path anyway - will cause error but at least we tried
        return $fontPath;
    }

    /**
     * Load colors from config and custom definitions
     */
    protected function loadColors($image, array $customColors = []): void
    {
        $configColors = config('dynamic-image-composer.colors', []);
        $allColors = array_merge($configColors, $customColors);

        foreach ($allColors as $name => $rgb) {
            $this->colors[$name] = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        }
    }

    /**
     * Apply opacity to image
     */
    protected function applyOpacity($image, float $opacity): void
    {
        $w = imagesx($image);
        $h = imagesy($image);

        $opacityImage = imagecreatetruecolor($w, $h);
        imagealphablending($opacityImage, false);
        imagesavealpha($opacityImage, true);

        $transparent = imagecolorallocatealpha($opacityImage, 0, 0, 0, 127);
        imagefill($opacityImage, 0, 0, $transparent);

        imagecopymerge($opacityImage, $image, 0, 0, 0, 0, $w, $h, $opacity * 100);
        imagecopy($image, $opacityImage, 0, 0, 0, 0, $w, $h);
        imagedestroy($opacityImage);
    }
}
