<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationEntitlement extends Model
{
    public const AVAILABLE = 'available';
    public const CLAIMED = 'claimed';

    protected $fillable = [
        'customer_id',
        'event_package_id',
        'exact_guest_capacity',
        'status',
        'claimed_event_id',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'exact_guest_capacity' => 'integer',
            'claimed_at' => 'immutable_datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(EventPackage::class, 'event_package_id');
    }

    public function claimedEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'claimed_event_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === self::AVAILABLE && $this->claimed_event_id === null;
    }
}
