<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    public const COMPONENT_KEYS = ['elegant-classic', 'modern-minimal', 'romantic-floral', 'editorial-luxury', 'modern-cinematic'];

    protected $fillable = ['name', 'slug', 'description', 'category', 'component_key', 'supported_event_types', 'default_settings', 'is_active', 'is_customer_selectable', 'display_order'];

    protected function casts(): array
    {
        return ['supported_event_types' => 'array', 'default_settings' => 'array', 'is_active' => 'boolean', 'is_customer_selectable' => 'boolean', 'display_order' => 'integer'];
    }

    public function events(): HasMany { return $this->hasMany(Event::class); }
}
