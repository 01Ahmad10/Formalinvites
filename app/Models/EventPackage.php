<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class EventPackage extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'minimum_guests', 'maximum_guests', 'price', 'is_active'];
    protected function casts(): array { return ['price' => 'decimal:2', 'is_active' => 'boolean']; }
    public function events(): HasMany { return $this->hasMany(Event::class); }
    public function scopeCapacityOrder(Builder $query): Builder { return $query->orderBy('minimum_guests')->orderBy('maximum_guests'); }
}
