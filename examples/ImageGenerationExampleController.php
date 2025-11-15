<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use Molham\DynamicImageComposer\DynamicImageComposer;
use Molham\DynamicImageComposer\Models\ImageTemplate;
use Molham\DynamicImageComposer\Services\TemplateImageService;

/**
 * Example controller showing different ways to use the package
 *
 * These are usage examples - adapt to your needs!
 */
class ImageGenerationExampleController extends Controller
{
    /**
     * Example 1: Basic image generation with direct configuration
     */
    public function generateBasicImage()
    {
        $composer = new DynamicImageComposer();

        $image = $composer->generate(
            templatePath: 'templates/certificate-template.png',
            fields: [
                'name' => [
                    'value' => 'John Doe',
                    'x' => 'center',
                    'y' => 1200,
                    'fontSize' => 100,
                    'color' => 'black',
                    'font' => 'monotype',
                    'alignment' => 'center'
                ],
                'course' => [
                    'value' => 'Advanced Laravel Development',
                    'x' => 'center',
                    'y' => 1500,
                    'fontSize' => 60,
                    'color' => 'gray',
                    'font' => 'default',
                    'alignment' => 'center'
                ],
                'date' => [
                    'value' => date('F d, Y'),
                    'x' => 'center',
                    'y' => 2000,
                    'fontSize' => 40,
                    'color' => 'black',
                ]
            ]
        );

        // Output directly
        return $composer->output($image, 'certificate.png');
    }

    /**
     * Example 2: Generate and save to storage
     */
    public function generateAndSave()
    {
        $composer = new DynamicImageComposer();

        $image = $composer->generate(
            'templates/badge-template.png',
            [
                'title' => [
                    'value' => 'Top Performer',
                    'x' => 'center',
                    'y' => 300,
                    'fontSize' => 80,
                    'color' => 'gold',
                    'alignment' => 'center'
                ]
            ]
        );

        $result = $composer->save($image, 'badge-' . time() . '.png');

        return response()->json([
            'success' => true,
            'url' => $result['url'],
            'path' => $result['path']
        ]);
    }

    /**
     * Example 3: Using database template
     */
    public function generateFromTemplate(TemplateImageService $service)
    {
        // Get active template
        $template = ImageTemplate::where('is_active', true)->first();

        if (!$template) {
            return response()->json(['error' => 'No active template found'], 404);
        }

        // Generate with field values
        $image = $service->generateFromTemplate($template, [
            'name' => 'Jane Smith',
            'course' => 'Web Development Bootcamp',
            'date' => date('Y-m-d'),
            'code' => 'CERT-2024-001'
        ]);

        return $service->generateAndOutput($template, [
            'name' => 'Jane Smith',
            'course' => 'Web Development Bootcamp',
            'date' => date('Y-m-d'),
        ], 'certificate.png');
    }

    /**
     * Example 4: With image overlays
     */
    public function generateWithOverlays()
    {
        $composer = new DynamicImageComposer();

        // Create base image with text
        $image = $composer->generate(
            'templates/social-media-template.png',
            [
                'headline' => [
                    'value' => 'Join Us Today!',
                    'x' => 'center',
                    'y' => 400,
                    'fontSize' => 120,
                    'color' => 'white',
                    'alignment' => 'center'
                ]
            ]
        );

        // Add logo overlay
        $composer->addOverlay($image, 'logos/company-logo.png', [
            'x' => 50,
            'y' => 50,
            'width' => 200,
            'height' => 200,
            'opacity' => 1.0
        ]);

        // Add watermark
        $composer->addOverlay($image, 'watermarks/watermark.png', [
            'x' => 1500,
            'y' => 1500,
            'width' => 300,
            'height' => 100,
            'opacity' => 0.5
        ]);

        return $composer->output($image, 'social-post.png');
    }

    /**
     * Example 5: Arabic text support
     */
    public function generateArabicCertificate()
    {
        $composer = new DynamicImageComposer();

        $image = $composer->generate(
            'templates/arabic-certificate.png',
            [
                'name' => [
                    'value' => 'محمد أحمد السيد',
                    'x' => 'center',
                    'y' => 1200,
                    'fontSize' => 100,
                    'color' => 'black',
                    'font' => 'default', // Will automatically use Arabic font variant
                    'alignment' => 'center'
                ],
                'course' => [
                    'value' => 'دورة تطوير تطبيقات الويب',
                    'x' => 'center',
                    'y' => 1500,
                    'fontSize' => 60,
                    'color' => 'gray',
                    'alignment' => 'center'
                ]
            ]
        );

        return $composer->output($image);
    }

    /**
     * Example 6: Dynamic ID card generation
     */
    public function generateIdCard($userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $composer = new DynamicImageComposer();

        $image = $composer->generate(
            'templates/id-card-template.png',
            [
                'name' => [
                    'value' => $user->name,
                    'x' => 400,
                    'y' => 200,
                    'fontSize' => 40,
                    'color' => 'black',
                ],
                'id_number' => [
                    'value' => $user->id,
                    'x' => 400,
                    'y' => 280,
                    'fontSize' => 30,
                    'color' => 'gray',
                ],
                'department' => [
                    'value' => $user->department ?? 'General',
                    'x' => 400,
                    'y' => 340,
                    'fontSize' => 30,
                    'color' => 'gray',
                ]
            ]
        );

        // Add profile photo if available
        if ($user->avatar) {
            $composer->addOverlay($image, $user->avatar, [
                'x' => 50,
                'y' => 150,
                'width' => 300,
                'height' => 300,
                'opacity' => 1.0
            ]);
        }

        return $composer->output($image, "id-card-{$user->id}.png");
    }
}
