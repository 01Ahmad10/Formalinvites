<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;
    public const TYPES = ['wedding', 'engagement', 'bridal_shower', 'birthday', 'graduation', 'baptism', 'communion'];
    public const STATUSES = ['draft', 'submitted', 'under_review', 'changes_requested', 'approved', 'published', 'archived'];
    protected $fillable = ['customer_id', 'event_package_id', 'title', 'event_type', 'host_name', 'second_host_name', 'description', 'main_date', 'start_time', 'end_time', 'venue', 'address', 'location_url', 'rsvp_deadline', 'event_timezone', 'dress_code', 'parking_information', 'transportation_information', 'accommodation_information', 'guest_information', 'status'];
    protected function casts(): array { return ['main_date' => 'date', 'rsvp_deadline' => 'date']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function package(): BelongsTo { return $this->belongsTo(EventPackage::class, 'event_package_id'); }
    public function members(): BelongsToMany { return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps(); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function invitationParties(): HasMany { return $this->hasMany(InvitationParty::class); }
    public function mealOptions(): HasMany { return $this->hasMany(EventMealOption::class); }
    public function activities(): HasMany { return $this->hasMany(EventActivity::class); }

    public function rsvpDeadlineAt(): ?CarbonImmutable
    {
        $deadline = $this->getRawOriginal('rsvp_deadline');
        if (! $deadline) return null;

        $timezone = $this->event_timezone ?: config('app.timezone');

        // The current column is a date. SQLite represents that as midnight text,
        // but it still means the full local calendar day rather than UTC midnight.
        if (($this->getCasts()['rsvp_deadline'] ?? null) === 'date') {
            return CarbonImmutable::createFromFormat('!Y-m-d', substr($deadline, 0, 10), $timezone)->endOfDay();
        }

        // Keeps the rule safe if a future migration changes the field to a datetime.
        return CarbonImmutable::parse($deadline, $timezone)->setTimezone($timezone);
    }

    public function isRsvpClosed(?CarbonImmutable $now = null): bool
    {
        $deadline = $this->rsvpDeadlineAt();

        return $deadline !== null && ($now ?? CarbonImmutable::now($this->event_timezone ?: config('app.timezone')))->greaterThanOrEqualTo($deadline);
    }

    public function allocatedGuestCapacity(?int $exceptPartyId = null): int
    {
        return (int) $this->invitationParties()->where('is_active', true)->when($exceptPartyId, fn ($query) => $query->where('id', '!=', $exceptPartyId))->sum('maximum_party_size');
    }
}
