<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartyMember extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['adult', 'child'];

    protected $fillable = ['invitation_party_id', 'first_name', 'last_name', 'member_type', 'notes'];

    public function party(): BelongsTo { return $this->belongsTo(InvitationParty::class, 'invitation_party_id'); }
}
