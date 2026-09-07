<?php

namespace App\Models\AR;

use App\Models\Upload\UploadPortalCustomer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPayment extends Model
{
    protected $table = 'customer_payments';

    protected $fillable = [
        'qq_payment_id',
        'customer_id',
        'payment_date',
        'payment_type',
        'total_amount',
        'amount_received',
        'credit_applied',
        'unapplied_amount',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'credit_applied' => 'decimal:2',
        'unapplied_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(UploadPortalCustomer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerPaymentItem::class, 'customer_payment_id');
    }

    public function creditItems(): HasMany
    {
        return $this->hasMany(CustomerPaymentCreditItem::class, 'customer_payment_id');
    }

    public function sourcedCreditItems(): HasMany
    {
        return $this->hasMany(CustomerPaymentCreditItem::class, 'source_customer_payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }
}
