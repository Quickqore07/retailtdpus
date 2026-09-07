<?php

namespace App\Models\AP;

use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use App\Models\Traits\LogsActivity;
use App\Models\Upload\UploadDocument;
use App\Models\User;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoice extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'ap_purchase_invoices';

    public const STATUSES = ['draft', 'approved', 'paid', 'cancelled'];

    protected $fillable = [
        'workgroup_id',
        'company_id',
        'vendor_id',
        'invoice_date',
        'invoice_no',
        'due_date',
        'expense_id',
        'amount',
        'other_amount',
        'total_amount',
        'remarks',
        'document_id',
        'status',
        'check_number',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'workgroup_id', 'label' => 'Workgroup', 'table' => 'workgroup', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'vendor_id', 'label' => 'Vendor', 'table' => 'ap_vendors', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'invoice_date', 'label' => 'Invoice Date', 'type' => 'date'],
        ['field' => 'invoice_no', 'label' => 'Invoice No', 'type' => 'string'],
        ['field' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
        ['field' => 'expense_id', 'label' => 'Expense Type', 'table' => 'ap_expense_types', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'other_amount', 'label' => 'Other Amount', 'type' => 'number'],
        ['field' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number'],
        ['field' => 'remarks', 'label' => 'Remarks', 'type' => 'string'],
        ['field' => 'document_id', 'label' => 'Document', 'table' => 'upload_documents', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'status', 'label' => 'Status', 'type' => 'string'],
        ['field' => 'check_number', 'label' => 'Check Number', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'other_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $sortable = [
        'invoice_date',
        'invoice_no',
        'due_date',
        'expense_id',
        'amount',
        'other_amount',
        'total_amount',
        'status',
        'created_at',
        'updated_at',
        'vendor_name',
        'company_name',
    ];

    protected $searchable = [
        'invoice_no',
        'remarks',
        'check_number',
        'status',
    ];

    protected $allowedFilters = [
        'workgroup_id',
        'company_id',
        'vendor_id',
        'invoice_date',
        'invoice_no',
        'due_date',
        'expense_id',
        'amount',
        'other_amount',
        'total_amount',
        'status',
        'created_at',
        'updated_at',
        'vendor_name',
        'company_name',
    ];

    protected $searchableColumns = [
        'invoice_no',
        'remarks',
        'check_number',
        'status',
        'total_amount',
        'amount',
        'other_amount',
        'invoice_date',
        'due_date',
        'created_at',
        'updated_at',
    ];

    public function workgroup()
    {
        return $this->belongsTo(Workgroup::class, 'workgroup_id')->select('id', 'name');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')
            ->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number, workgroup_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id')->select('id', 'name', 'email', 'mobile', 'credit_days');
    }

    public function expense()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_id')
            ->select('id',  'name', 'amount_label', 'other_amount_label', 'show_other_amount');
    }

    public function document()
    {
        return $this->belongsTo(UploadDocument::class, 'document_id')
            ->select('id', 'folder_id', 'company_id', 'name', 'file_path', 'file_name', 'file_type', 'file_size', 'uploaded_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchaseInvoicePayment::class, 'purchase_invoice_id');
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('amount'), 2);
        }

        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function getDueAmountAttribute(): float
    {
        $total = (float) ($this->total_amount ?? $this->amount ?? 0);

        return round(max($total - $this->paid_amount, 0), 2);
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
