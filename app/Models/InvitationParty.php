<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvitationParty extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'name', 'primary_contact_name', 'email', 'phone', 'maximum_party_size', 'table_name', 'notes', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['maximum_party_size' => 'integer', 'is_active' => 'boolean'];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function members(): HasMany { return $this->hasMany(PartyMember::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
