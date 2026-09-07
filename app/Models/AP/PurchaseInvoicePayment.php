<?php

namespace App\Models\AP;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoicePayment extends Model
{
    protected $table = 'ap_purchase_invoice_payments';

    protected $fillable = [
        'purchase_invoice_id',
        'amount',
        'payment_method',
        'check_number',
        'payment_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
