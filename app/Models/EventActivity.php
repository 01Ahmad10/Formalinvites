<?php

namespace App\Models;

use App\Casts\AsUtcInstant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventActivity extends Model
{
    protected $fillable = ['event_id', 'title', 'activity_type', 'description', 'starts_at', 'ends_at', 'venue', 'address', 'location_url', 'location_notes', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['starts_at' => AsUtcInstant::class, 'ends_at' => AsUtcInstant::class, 'is_active' => 'boolean', 'display_order' => 'integer'];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
