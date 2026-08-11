<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class EventMealOption extends Model { protected $fillable=['event_id','name','description','is_active','display_order']; protected function casts(): array { return ['is_active'=>'boolean','display_order'=>'integer']; } public function event(): BelongsTo { return $this->belongsTo(Event::class); } public function personResponses(): HasMany { return $this->hasMany(RsvpPersonResponse::class); } }
