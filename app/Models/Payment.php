<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['customer_id', 'event_id', 'event_package_id', 'original_amount', 'discount', 'final_amount', 'paid_amount', 'payment_method', 'reference', 'payment_date', 'status', 'notes'];
    protected function casts(): array { return ['original_amount' => 'decimal:2', 'discount' => 'decimal:2', 'final_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'payment_date' => 'date']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function package(): BelongsTo { return $this->belongsTo(EventPackage::class, 'event_package_id'); }
}
