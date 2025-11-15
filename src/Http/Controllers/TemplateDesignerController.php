<?php

namespace Badrshs\DynamicImageComposer\Http\Controllers;

use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Badrshs\DynamicImageComposer\Models\TemplateElement;
use Badrshs\DynamicImageComposer\DynamicImageComposer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TemplateDesignerController
{
    protected DynamicImageComposer $composer;

    public function __construct(DynamicImageComposer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * Show the designer interface
     */
    public function show(ImageTemplate $template)
    {
        $template->load('elements');

        return view('dynamic-image-composer::designer.index', [
            'template' => $template
        ]);
    }

    /**
     * Get current configuration
     */
    public function getConfiguration(ImageTemplate $template)
    {
        return response()->json([
            'template' => $template->only(['id', 'name', 'width', 'height', 'background_image']),
            'elements' => $template->elements->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'type' => $e->element_type,
                'image_path' => $e->image_path,
                'x_position' => (int) $e->x_position,
                'y_position' => (int) $e->y_position,
                'width' => (int) $e->width,
                'height' => (int) $e->height,
                'z_index' => (int) ($e->z_index ?? 1),
                'opacity' => (float) ($e->opacity ?? 1),
            ])->values(),
            'fields' => $template->field_configuration['fields'] ?? []
        ]);
    }

    /**
     * Generate preview image
     */
    public function preview(ImageTemplate $template)
    {
        try {
            Log::info('Generating preview for template', ['template_id' => $template->id]);

            // Create base image
            $image = $this->createBaseImage($template);

            // Add all elements
            foreach ($template->elements()->orderBy('z_index')->get() as $element) {
                if ($element->image_path) {
                    $this->addElementToImage($image, $element);
                }
            }

            // Add text field markers (sample text)
            $fields = $template->field_configuration['fields'] ?? [];
            foreach ($fields as $fieldName => $fieldConfig) {
                if (is_numeric($fieldName)) {
                    // Array-based fields
                    $fieldConfig['value'] = $fieldConfig['label'] ?? 'Sample';
                } else {
                    $fieldConfig['value'] = ucfirst($fieldName);
                }

                $this->addTextField($image, $fieldConfig, $template->width, $template->height);
            }

            return $this->composer->output($image, 'preview.png');
        } catch (\Throwable $e) {
            Log::error('Preview generation failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Preview failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload a new element
     */
    public function uploadElement(Request $request, ImageTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|max:10240',
            'x_position' => 'required|integer',
            'y_position' => 'required|integer',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
        ]);

        $disk = config('dynamic-image-composer.disk', 'public');
        $directory = config('dynamic-image-composer.elements_directory', 'image-elements');

        // Store image
        $path = $request->file('image')->store($directory, $disk);

        // Get image dimensions if not provided
        $imagePath = Storage::disk($disk)->path($path);
        $imageInfo = getimagesize($imagePath);
        $width = $request->input('width', $imageInfo[0] ?? 200);
        $height = $request->input('height', $imageInfo[1] ?? 200);

        // Create element
        $element = $template->elements()->create([
            'name' => $request->input('name'),
            'element_type' => 'image',
            'image_path' => $path,
            'x_position' => $request->input('x_position'),
            'y_position' => $request->input('y_position'),
            'width' => $width,
            'height' => $height,
            'z_index' => $template->elements()->max('z_index') + 1,
            'opacity' => 1.0,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'element' => [
                'id' => $element->id,
                'name' => $element->name,
                'type' => $element->element_type,
                'image_path' => $element->image_path,
                'x_position' => $element->x_position,
                'y_position' => $element->y_position,
                'width' => $element->width,
                'height' => $element->height,
                'z_index' => $element->z_index,
                'opacity' => $element->opacity,
            ]
        ]);
    }

    /**
     * Update element properties
     */
    public function updateElement(Request $request, ImageTemplate $template, TemplateElement $element)
    {
        $request->validate([
            'x_position' => 'nullable|integer',
            'y_position' => 'nullable|integer',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
            'z_index' => 'nullable|integer',
            'opacity' => 'nullable|numeric|min:0|max:1',
        ]);

        $element->update($request->only([
            'x_position',
            'y_position',
            'width',
            'height',
            'z_index',
            'opacity'
        ]));

        return response()->json([
            'success' => true,
            'element' => $element
        ]);
    }

    /**
     * Delete element
     */
    public function deleteElement(ImageTemplate $template, TemplateElement $element)
    {
        // Delete image file if exists
        if ($element->image_path) {
            Storage::disk(config('dynamic-image-composer.disk', 'public'))
                ->delete($element->image_path);
        }

        $element->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Save complete configuration
     */
    public function saveConfiguration(Request $request, ImageTemplate $template)
    {
        $request->validate([
            'elements' => 'nullable|array',
            'elements.*.id' => 'required|integer',
            'elements.*.x_position' => 'required|integer',
            'elements.*.y_position' => 'required|integer',
            'elements.*.width' => 'required|integer',
            'elements.*.height' => 'required|integer',
            'elements.*.z_index' => 'nullable|integer',
            'elements.*.opacity' => 'nullable|numeric',
            'fields' => 'nullable|array',
        ]);

        // Update all elements
        if ($request->has('elements')) {
            foreach ($request->input('elements') as $elementData) {
                TemplateElement::where('id', $elementData['id'])
                    ->where('template_id', $template->id)
                    ->update([
                        'x_position' => $elementData['x_position'],
                        'y_position' => $elementData['y_position'],
                        'width' => $elementData['width'],
                        'height' => $elementData['height'],
                        'z_index' => $elementData['z_index'] ?? 1,
                        'opacity' => $elementData['opacity'] ?? 1.0,
                    ]);
            }
        }

        // Update field configuration
        if ($request->has('fields')) {
            $template->update([
                'field_configuration' => [
                    'fields' => $request->input('fields')
                ]
            ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Generate final composite image
     */
    public function generateFinalCertificate(Request $request, ImageTemplate $template)
    {
        try {
            // Create base image
            $image = $this->createBaseImage($template);

            // Add all elements
            foreach ($template->elements()->orderBy('z_index')->get() as $element) {
                if ($element->image_path) {
                    $this->addElementToImage($image, $element);
                }
            }

            // Save the final composite
            $filename = 'template-' . $template->id . '-' . time() . '.png';
            $disk = config('dynamic-image-composer.disk', 'public');
            $directory = config('dynamic-image-composer.generated_directory', 'generated-images');

            Storage::disk($disk)->makeDirectory($directory);
            $path = $directory . '/' . $filename;
            $fullPath = Storage::disk($disk)->path($path);

            imagepng($image, $fullPath);

            // Update template with final image
            $template->update([
                'final_template_image' => $path
            ]);

            imagedestroy($image);

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk($disk)->url($path),
                'filename' => $filename
            ]);
        } catch (\Throwable $e) {
            Log::error('Final generation failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create base image from template background
     */
    protected function createBaseImage(ImageTemplate $template)
    {
        $disk = config('dynamic-image-composer.disk', 'public');
        $backgroundPath = Storage::disk($disk)->path($template->background_image);

        if (!file_exists($backgroundPath)) {
            throw new \Exception("Background image not found: {$template->background_image}");
        }

        $backgroundImage = imagecreatefrompng($backgroundPath);
        if (!$backgroundImage) {
            $backgroundImage = imagecreatefromjpeg($backgroundPath);
        }

        if (!$backgroundImage) {
            throw new \Exception('Could not create image from background');
        }

        $canvas = imagecreatetruecolor($template->width, $template->height);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled(
            $canvas,
            $backgroundImage,
            0,
            0,
            0,
            0,
            $template->width,
            $template->height,
            imagesx($backgroundImage),
            imagesy($backgroundImage)
        );

        imagedestroy($backgroundImage);

        return $canvas;
    }

    /**
     * Add element to image
     */
    protected function addElementToImage($canvas, TemplateElement $element)
    {
        $disk = config('dynamic-image-composer.disk', 'public');
        $imagePath = Storage::disk($disk)->path($element->image_path);

        if (!file_exists($imagePath)) {
            Log::warning('Element image not found', ['path' => $element->image_path]);
            return;
        }

        $elementImage = $this->createImageFromFile($imagePath);
        if (!$elementImage) {
            return;
        }

        // Resize if needed
        if ($element->width !== imagesx($elementImage) || $element->height !== imagesy($elementImage)) {
            $resized = imagecreatetruecolor($element->width, $element->height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);

            imagecopyresampled(
                $resized,
                $elementImage,
                0,
                0,
                0,
                0,
                $element->width,
                $element->height,
                imagesx($elementImage),
                imagesy($elementImage)
            );

            imagedestroy($elementImage);
            $elementImage = $resized;
        }

        // Apply opacity
        if ($element->opacity < 1.0) {
            $this->applyOpacity($elementImage, $element->opacity);
        }

        imagecopy($canvas, $elementImage, $element->x_position, $element->y_position, 0, 0, $element->width, $element->height);
        imagedestroy($elementImage);
    }

    /**
     * Add text field to image
     */
    protected function addTextField($image, array $config, int $imageWidth, int $imageHeight): void
    {
        if (!isset($config['value']) || empty($config['value'])) {
            return;
        }

        $text = (string) $config['value'];

        // Check if text is Arabic
        $isArabic = preg_match('/\p{Arabic}/u', $text) === 1;

        if ($isArabic) {
            $arabic = new \ArPHP\I18N\Arabic();
            $text = $arabic->utf8Glyphs($text);
        }

        // Get font
        $fontStyle = $config['font'] ?? 'default';
        $langKey = $isArabic ? 'ar' : 'en';
        $fonts = config('dynamic-image-composer.fonts', []);
        $fontFile = $fonts[$fontStyle][$langKey] ?? $fonts['default'][$langKey] ?? 'Museo500-Regular.ttf';
        $fontPath = public_path('fonts/' . $fontFile);

        // Get color
        $colorKey = $config['color'] ?? 'black';
        $colors = config('dynamic-image-composer.colors', []);
        $rgb = $colors[$colorKey] ?? $colors['black'] ?? [0, 0, 0];
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

        // Get font size
        $fontSize = $config['fontSize'] ?? $config['font_size'] ?? 20;

        // Calculate X position based on alignment
        $x = $config['x'] ?? 0;
        $alignment = $config['alignment'] ?? 'left';

        if ($x === 'center' || $alignment === 'center') {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $box[2] - $box[0];
            $x = ($imageWidth - $textWidth) / 2;
        } elseif ($alignment === 'right') {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $box[2] - $box[0];
            $x = $imageWidth - $textWidth - ($x ?? 0);
        }

        $y = $config['y'] ?? 0;

        // Add text
        if (file_exists($fontPath)) {
            imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
        }
    }

    /**
     * Create image from file
     */
    protected function createImageFromFile(string $path)
    {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return false;
        }

        return match ($imageInfo[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => false,
        };
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
