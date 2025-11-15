<?php

namespace Molham\DynamicImageComposer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'background_image',
        'width',
        'height',
        'is_active',
        'settings',
        'final_template_image',
        'field_configuration',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'field_configuration' => 'array',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Get all elements for this template
     */
    public function elements(): HasMany
    {
        return $this->hasMany(TemplateElement::class, 'template_id');
    }

    /**
     * Get active elements ordered by z-index
     */
    public function activeElements(): HasMany
    {
        return $this->elements()
            ->where('is_active', true)
            ->orderBy('z_index');
    }
}
