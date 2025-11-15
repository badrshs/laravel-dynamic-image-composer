<?php

namespace Badrshs\DynamicImageComposer\Services;

use Badrshs\DynamicImageComposer\DynamicImageComposer;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Advanced service for working with database templates and elements
 */
class TemplateImageService
{
    protected DynamicImageComposer $composer;

    public function __construct(DynamicImageComposer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * Generate image from database template
     *
     * @param ImageTemplate $template
     * @param array $fieldValues Key-value pairs for field values
     * @param array $additionalOptions Custom options to override template settings
     * @return \GdImage
     */
    public function generateFromTemplate(
        ImageTemplate $template,
        array $fieldValues = [],
        array $additionalOptions = []
    ) {
        Log::info('Generating image from template', [
            'template_id' => $template->id,
            'template_name' => $template->name
        ]);

        // Load field configuration from template
        $fieldConfig = $template->field_configuration['fields'] ?? [];

        // Merge field values with configuration
        $fields = [];
        foreach ($fieldConfig as $fieldName => $config) {
            if (isset($fieldValues[$fieldName])) {
                $fields[$fieldName] = array_merge($config, [
                    'value' => $fieldValues[$fieldName]
                ]);
            }
        }

        // Generate base image
        $image = $this->composer->generate(
            $template->background_image,
            $fields,
            $additionalOptions
        );

        // Add template elements (overlays)
        foreach ($template->activeElements as $element) {
            if ($element->element_type === 'image' && $element->image_path) {
                $this->composer->addOverlay($image, $element->image_path, [
                    'x' => $element->x_position,
                    'y' => $element->y_position,
                    'width' => $element->width,
                    'height' => $element->height,
                    'opacity' => $element->opacity,
                ]);
            }
        }

        Log::info('Image generated successfully from template');

        return $image;
    }

    /**
     * Generate and save image from template
     *
     * @param ImageTemplate $template
     * @param array $fieldValues
     * @param string|null $filename
     * @param array $additionalOptions
     * @return array
     */
    public function generateAndSave(
        ImageTemplate $template,
        array $fieldValues = [],
        ?string $filename = null,
        array $additionalOptions = []
    ): array {
        $image = $this->generateFromTemplate($template, $fieldValues, $additionalOptions);

        if (!$filename) {
            $filename = 'image-' . $template->id . '-' . time() . '.png';
        }

        return $this->composer->save($image, $filename);
    }

    /**
     * Get active template by ID or get default active template
     */
    public function getTemplate(?int $templateId = null): ?ImageTemplate
    {
        if ($templateId) {
            return ImageTemplate::find($templateId);
        }

        return ImageTemplate::where('is_active', true)->first();
    }

    /**
     * Generate image and return as HTTP response
     */
    public function generateAndOutput(
        ImageTemplate $template,
        array $fieldValues = [],
        string $filename = 'image.png',
        array $additionalOptions = []
    ) {
        $image = $this->generateFromTemplate($template, $fieldValues, $additionalOptions);
        return $this->composer->output($image, $filename);
    }
}
