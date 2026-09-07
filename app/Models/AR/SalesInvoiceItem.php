<?php

namespace App\Models\AR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'sales_invoice_items';

    protected $fillable = [
        'sales_invoice_id',
        'item_name',
        'qty',
        'unit_price',
        'discount',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
