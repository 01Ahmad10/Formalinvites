<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RsvpPersonResponse extends Model { protected $fillable=['rsvp_id','party_member_id','first_name','last_name','member_type','is_original_party_member','is_attending','event_meal_option_id','dietary_note']; protected function casts(): array { return ['is_original_party_member'=>'boolean','is_attending'=>'boolean']; } public function rsvp(): BelongsTo { return $this->belongsTo(Rsvp::class); } public function partyMember(): BelongsTo { return $this->belongsTo(PartyMember::class); } public function mealOption(): BelongsTo { return $this->belongsTo(EventMealOption::class,'event_meal_option_id'); } }
