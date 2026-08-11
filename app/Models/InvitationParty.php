<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InvitationParty extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'rsvp_token', 'name', 'primary_contact_name', 'email', 'phone', 'maximum_party_size', 'table_name', 'notes', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['maximum_party_size' => 'integer', 'is_active' => 'boolean'];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function members(): HasMany { return $this->hasMany(PartyMember::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function rsvp(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Rsvp::class); }
    protected static function booted(): void { static::creating(function (self $party): void { $party->rsvp_token ??= Str::random(64); }); static::created(fn (self $party) => $party->rsvp()->firstOrCreate([], ['status' => 'pending'])); }
}
