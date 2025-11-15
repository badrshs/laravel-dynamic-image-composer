<?php

namespace Badrshs\DynamicImageComposer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \GdImage generate(string $templatePath, array $fields, array $options = [])
 * @method static array save(\GdImage $image, string $filename, ?string $disk = null)
 * @method static \Illuminate\Http\Response output(\GdImage $image, string $filename = 'image.png')
 * @method static void addOverlay(\GdImage $baseImage, string $overlayPath, array $config)
 *
 * @see \Badrshs\DynamicImageComposer\DynamicImageComposer
 */
class ImageComposer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Badrshs\DynamicImageComposer\DynamicImageComposer::class;
    }
}
