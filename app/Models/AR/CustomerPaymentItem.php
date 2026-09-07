<?php

namespace App\Models\AR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentItem extends Model
{
    protected $table = 'customer_payment_items';

    protected $fillable = [
        'customer_payment_id',
        'sales_invoice_id',
        'amount_applied',
        'discount',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
