<?php

namespace App\Models\AR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentCreditItem extends Model
{
    protected $table = 'customer_payment_credit_items';

    protected $fillable = [
        'customer_payment_id',
        'source_customer_payment_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    public function sourcePayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'source_customer_payment_id');
    }
}
