<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const STATUSES = ['unpaid', 'partially_paid', 'paid'];

    protected $fillable = ['customer_id', 'event_id', 'event_package_id', 'coupon_id', 'coupon_code', 'original_amount', 'discount', 'discount_type', 'discount_value', 'final_amount', 'paid_amount', 'payment_method', 'reference', 'payment_date', 'status', 'notes'];

    protected function casts(): array { return ['original_amount' => 'decimal:2', 'discount' => 'decimal:2', 'discount_value' => 'decimal:2', 'final_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'payment_date' => 'date']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function package(): BelongsTo { return $this->belongsTo(EventPackage::class, 'event_package_id'); }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function transactions(): HasMany { return $this->hasMany(PaymentTransaction::class); }
    public function latestTransaction(): HasOne { return $this->hasOne(PaymentTransaction::class)->latestOfMany(); }

    public function remainingAmount(): float
    {
        return max((float) $this->final_amount - (float) $this->paid_amount, 0);
    }

    public function refreshTotals(): void
    {
        $paidAmount = round((float) $this->transactions()->where('status', 'confirmed')->sum('amount'), 2);
        $finalAmount = max((float) $this->final_amount, 0);
        $status = $paidAmount >= $finalAmount ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');

        $this->forceFill(['paid_amount' => $paidAmount, 'status' => $status])->save();
    }
}
