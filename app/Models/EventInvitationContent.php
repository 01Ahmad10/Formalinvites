<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventInvitationContent extends Model
{
    protected $fillable = [
        'event_id', 'primary_locale', 'story_enabled', 'story_heading', 'story_body',
        'gift_registry_enabled', 'gift_registry_intro', 'ending_enabled', 'ending_title', 'ending_message',
    ];

    protected function casts(): array
    {
        return ['story_enabled' => 'boolean', 'gift_registry_enabled' => 'boolean', 'ending_enabled' => 'boolean'];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
