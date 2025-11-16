<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The disk to use for storing templates and generated images
    |
    */
    'disk' => env('DYNAMIC_IMAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Templates Directory
    |--------------------------------------------------------------------------
    |
    | Directory where template images are stored
    |
    */
    'templates_directory' => 'image-templates',

    /*
    |--------------------------------------------------------------------------
    | Elements Directory
    |--------------------------------------------------------------------------
    |
    | Directory where template element images are stored
    |
    */
    'elements_directory' => 'image-elements',

    /*
    |--------------------------------------------------------------------------
    | Generated Images Directory
    |--------------------------------------------------------------------------
    |
    | Directory where generated images will be saved
    |
    */
    'generated_directory' => 'generated-images',

    /*
    |--------------------------------------------------------------------------
    | Fonts Directory
    |--------------------------------------------------------------------------
    |
    | Directory where custom fonts are stored. Can be:
    | - 'storage' (uses storage_path('app/public/fonts'))
    | - 'public' (uses public_path('fonts'))
    |
    */
    'fonts_storage' => env('DYNAMIC_IMAGE_FONTS_STORAGE', 'storage'), // 'storage' or 'public'

    'fonts_directory' => 'fonts',

    /*
    |--------------------------------------------------------------------------
    | Default Font Definitions
    |--------------------------------------------------------------------------
    |
    | Define font families with language-specific variants
    | Uses fonts from the configured fonts_directory
    |
    */
    'fonts' => [
        'default' => [
            'en' => 'Roboto-Regular.ttf',
            'ar' => 'NotoKufiArabic-Regular.ttf',
        ],
        'libre' => [
            'en' => 'Roboto-Regular.ttf',
            'ar' => 'NotoKufiArabic-Regular.ttf',
        ],
        'monotype' => [
            'en' => 'Roboto-Regular.ttf',
            'ar' => 'NotoKufiArabic-Regular.ttf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Definitions
    |--------------------------------------------------------------------------
    |
    | Pre-defined colors for text (RGB values)
    |
    */
    'colors' => [
        'black' => [40, 40, 40],
        'white' => [255, 255, 255],
        'gold' => [212, 175, 55],
        'green' => [122, 180, 82],
        'blue' => [25, 25, 112],
        'gray' => [128, 128, 128],
        'brown' => [170, 135, 77],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Quality
    |--------------------------------------------------------------------------
    |
    | Quality for generated PNG images (0-9, 0 = best compression, 9 = best quality)
    |
    */
    'image_quality' => 9,

    /*
    |--------------------------------------------------------------------------
    | Cache Generated Images
    |--------------------------------------------------------------------------
    |
    | Whether to cache generated images
    |
    */
    'cache_generated' => true,
];
