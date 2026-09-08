<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'is_active', 'allowed_events'];
    protected function casts(): array { return ['is_active' => 'boolean', 'allowed_events' => 'integer']; }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function events(): HasMany { return $this->hasMany(Event::class); }

    /** All created Events consume entitlement, including archived ones. */
    public function usedEvents(): int
    {
        return array_key_exists('events_count', $this->attributes)
            ? (int) $this->attributes['events_count']
            : $this->events()->count();
    }

    public function remainingEvents(): int
    {
        return max((int) $this->allowed_events - $this->usedEvents(), 0);
    }

    public function canCreateEvent(): bool
    {
        return $this->usedEvents() < (int) $this->allowed_events;
    }

    public function allowanceSummary(): array
    {
        return [
            'allowed_events' => (int) $this->allowed_events,
            'used_events' => $this->usedEvents(),
            'remaining_events' => $this->remainingEvents(),
            'can_create_event' => $this->canCreateEvent(),
        ];
    }
}
