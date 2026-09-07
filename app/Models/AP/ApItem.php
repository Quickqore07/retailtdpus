<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Model;
use App\Models\Upload\UploadDocument;
use App\Models\Settings\Company;

class ApItem extends Model
{
    protected $fillable = [
        'ap_invoice_id',
        'document_id',
        'company_id',
        'qq_ledger_id',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number');
    }

    public function apInvoice()
    {
        return $this->belongsTo(ApInvoice::class, 'ap_invoice_id');
    }

    public function document()
    {
        return $this->belongsTo(UploadDocument::class, 'document_id');
    }

}
