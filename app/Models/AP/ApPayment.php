<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Settings\Company;

class ApPayment extends Model
{
    protected $fillable = [
        'qq_paybill_id',
        'company_id',
        'qq_company_id',
        'qq_business_unit_id',
        'date',
        'qq_vendor_id',
        'qq_bank_id',
        'payment_type',
        'qq_ledger_id',
        'check_no',
        'memo',
        'amount',
        'created_by',
        'updated_by',
        'check_document_id',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number');
    }

    public function items()
    {
        return $this->hasMany(ApPaymentItem::class, 'ap_payment_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }
}
