<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class Rsvp extends Model { public const STATUSES=['pending','attending','not_attending']; protected $fillable=['invitation_party_id','status','submitted_at','last_updated_at','guest_message']; protected function casts(): array { return ['submitted_at'=>'datetime','last_updated_at'=>'datetime']; } public function party(): BelongsTo { return $this->belongsTo(InvitationParty::class,'invitation_party_id'); } public function personResponses(): HasMany { return $this->hasMany(RsvpPersonResponse::class); } }
