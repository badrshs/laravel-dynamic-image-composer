<?php

namespace Badrshs\DynamicImageComposer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateElement extends Model
{
    protected $fillable = [
        'template_id',
        'name',
        'element_type',
        'image_path',
        'x_position',
        'y_position',
        'width',
        'height',
        'z_index',
        'opacity',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'x_position' => 'integer',
        'y_position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'z_index' => 'integer',
        'opacity' => 'float',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the template this element belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImageTemplate::class, 'template_id');
    }
}
