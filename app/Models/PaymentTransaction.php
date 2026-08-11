<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = ['payment_id', 'amount', 'payment_method', 'reference', 'payment_date', 'notes', 'created_by', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
