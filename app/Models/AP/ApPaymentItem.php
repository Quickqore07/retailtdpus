<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Model;

class ApPaymentItem extends Model
{
    protected $fillable = [
        'ap_payment_id',
        'bill_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function apPayment()
    {
        return $this->belongsTo(ApPayment::class, 'ap_payment_id');
    }

    public function bill()
    {
        return $this->belongsTo(ApInvoice::class, 'bill_id');
    }
}
