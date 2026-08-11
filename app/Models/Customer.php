<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function events(): HasMany { return $this->hasMany(Event::class); }
}
