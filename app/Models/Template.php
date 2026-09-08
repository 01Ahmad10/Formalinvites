<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    public const COMPONENT_KEYS = ['elegant-classic', 'modern-minimal', 'romantic-floral', 'editorial-luxury', 'modern-cinematic', 'dolce-vita', 'blossom-oud', 'sacred-garden'];

    public function getDemoUrlAttribute(): ?string
    {
        $number = ['romantic-floral' => 1, 'editorial-luxury' => 2, 'modern-cinematic' => 3, 'dolce-vita' => 5, 'blossom-oud' => 6, 'sacred-garden' => 7][$this->component_key] ?? null;

        return app()->environment('local') && $number ? route('template-demos', ['template' => $number], false) : null;
    }

    protected $fillable = ['name', 'slug', 'description', 'category', 'component_key', 'supported_event_types', 'default_settings', 'is_active', 'is_customer_selectable', 'display_order'];

    protected function casts(): array
    {
        return ['supported_event_types' => 'array', 'default_settings' => 'array', 'is_active' => 'boolean', 'is_customer_selectable' => 'boolean', 'display_order' => 'integer'];
    }

    public function events(): HasMany { return $this->hasMany(Event::class); }
}
