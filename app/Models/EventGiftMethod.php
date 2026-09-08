<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventGiftMethod extends Model
{
    protected $fillable = ['event_id', 'label', 'details', 'external_url', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['display_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
