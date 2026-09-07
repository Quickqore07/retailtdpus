<?php

namespace App\Models\AR;

use App\Models\Upload\UploadPortalCustomer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'qq_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_total',
        'tax_total',
        'amount',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attributes appended for API responses but not stored in the database.
     *
     * @var list<string>
     */
    protected array $virtualAttributes = [
        'amount_paid',
        'balance_due',
        'payment_summary',
        'company_name',
        'company_address',
    ];

    public function getDirty()
    {
        return array_diff_key(parent::getDirty(), array_flip($this->virtualAttributes));
    }

    public function customer()
    {
        return $this->belongsTo(UploadPortalCustomer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id');
    }

    public function paymentItems()
    {
        return $this->hasMany(CustomerPaymentItem::class, 'sales_invoice_id');
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
