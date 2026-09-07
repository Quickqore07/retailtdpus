<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Model;
use App\Models\Upload\UploadDocument;
use App\Models\Settings\Company;
use App\Models\User;

class ApInvoice extends Model
{
    protected $fillable = [
        'document_id',
        'qq_purchase_id',
        'company_id',
        'qq_vendor_id',
        'date',
        'bill_number',
        'qq_ledger_id',
        'due_date',
        'amount',
        'approved_amount',
        'status',
        'invoice_type',
        'remarks',
        'approval_remarks',
        'qq_company_id',
        'qq_business_unit_id',
        'check_document_id',
        'created_by',   
        'updated_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(UploadDocument::class, 'document_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number, workgroup_id');
    }

    public function items()
    {
        return $this->hasMany(ApItem::class, 'ap_invoice_id')->with('company');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by')->select('id', 'name');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by')->select('id', 'name');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by')->select('id', 'name');
    }
}
