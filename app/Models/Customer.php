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
    public function invitationEntitlements(): HasMany { return $this->hasMany(InvitationEntitlement::class); }

    /** Claimed entitlement slots, including archived Event slots, are used. */
    public function usedEvents(): int
    {
        return array_key_exists('claimed_entitlements_count', $this->attributes)
            ? (int) $this->attributes['claimed_entitlements_count']
            : $this->invitationEntitlements()->where('status', InvitationEntitlement::CLAIMED)->count();
    }

    public function remainingEvents(): int
    {
        return array_key_exists('available_entitlements_count', $this->attributes)
            ? (int) $this->attributes['available_entitlements_count']
            : $this->invitationEntitlements()->where('status', InvitationEntitlement::AVAILABLE)->count();
    }

    public function canCreateEvent(): bool
    {
        return $this->remainingEvents() > 0;
    }

    public function allowanceSummary(): array
    {
        return [
            'allowed_events' => $this->usedEvents() + $this->remainingEvents(),
            'used_events' => $this->usedEvents(),
            'remaining_events' => $this->remainingEvents(),
            'can_create_event' => $this->canCreateEvent(),
        ];
    }
}
