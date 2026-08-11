<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    public const DISCOUNT_TYPES = ['fixed', 'percentage'];

    protected $fillable = ['code', 'description', 'discount_type', 'discount_value', 'is_active', 'starts_at', 'expires_at', 'usage_limit'];

    protected function casts(): array
    {
        return ['discount_value' => 'decimal:2', 'is_active' => 'boolean', 'starts_at' => 'date', 'expires_at' => 'date'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isAvailableFor(?Payment $payment = null): bool
    {
        if (! $this->is_active || ($this->starts_at && $this->starts_at->startOfDay()->isFuture()) || ($this->expires_at && $this->expires_at->endOfDay()->isPast())) {
            return false;
        }

        $uses = $this->payments()->when($payment?->exists, fn ($query) => $query->where('id', '!=', $payment->id))->count();

        return $this->usage_limit === null || $uses < $this->usage_limit;
    }
}
