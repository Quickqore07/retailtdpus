<?php

namespace App\Models\ChargeBack;

use App\Models\Settings\Company;
use App\Models\Traits\LogsActivity;
use App\Models\Upload\UploadDocument;
use App\Models\User;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Chargeback extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    public bool $skipActivityLog = false;

    protected $appends = [
        'display_status',
    ];

    public const CARD_NETWORKS = ['Visa', 'Mastercard', 'Amex', 'Discover'];

    protected $table = 'chargebacks';

    protected $fillable = [
        'case_number',
        'network_case_number',
        'reference_number',
        'company_id',
        'reason_code',
        'amount',
        'card_network',
        'card_last_four',
        'entry_mode',
        'transaction_date',
        'chargeback_received_date',
        'processor_due_date',
        'notes',
        'document_id',
        'sales_receipt_document_id',
        'upload_sales_receipt_date',
        'sales_receipt_uploaded_by',
        'marked_as_received',
        'marked_as_received_by',
        'marked_as_received_at',
        'credited_date',
        'submitted',
        'submitted_by',
        'submitted_at',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'case_number', 'label' => 'Case Number', 'type' => 'string'],
        ['field' => 'network_case_number', 'label' => 'Network Case Number', 'type' => 'string'],
        ['field' => 'reference_number', 'label' => 'Reference Number', 'type' => 'string'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'reason_code', 'label' => 'Reason Code', 'type' => 'string'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'card_network', 'label' => 'Card Network', 'type' => 'string'],
        ['field' => 'card_last_four', 'label' => 'Card Last Four', 'type' => 'string'],
        ['field' => 'entry_mode', 'label' => 'Entry Mode', 'type' => 'string'],
        ['field' => 'transaction_date', 'label' => 'Transaction Date', 'type' => 'date'],
        ['field' => 'chargeback_received_date', 'label' => 'Chargeback Received Date', 'type' => 'date'],
        ['field' => 'processor_due_date', 'label' => 'Processor Due Date', 'type' => 'date'],
        ['field' => 'notes', 'label' => 'Notes', 'type' => 'string'],
        ['field' => 'document_id', 'label' => 'Document', 'table' => 'employee_documents', 'table_field' => 'document_name', 'type' => 'lookup'],
        ['field' => 'sales_receipt_document_id', 'label' => 'Sales Receipt Document Id', 'type' => 'string'],
        ['field' => 'upload_sales_receipt_date', 'label' => 'Upload Sales Receipt Date', 'type' => 'date'],
        ['field' => 'sales_receipt_uploaded_by', 'label' => 'Sales Receipt Uploaded By', 'type' => 'string'],
        ['field' => 'marked_as_received', 'label' => 'Marked As Received', 'type' => 'boolean'],
        ['field' => 'marked_as_received_by', 'label' => 'Marked As Received By', 'type' => 'string'],
        ['field' => 'marked_as_received_at', 'label' => 'Marked As Received At', 'type' => 'date'],
        ['field' => 'credited_date', 'label' => 'Credited Date', 'type' => 'date'],
        ['field' => 'submitted', 'label' => 'Submitted', 'type' => 'boolean'],
        ['field' => 'submitted_by', 'label' => 'Submitted By', 'type' => 'string'],
        ['field' => 'submitted_at', 'label' => 'Submitted At', 'type' => 'date'],
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
        'marked_as_received' => 'boolean',
        'submitted' => 'boolean',
        'submitted_by' => 'integer',
        'submitted_at' => 'datetime',
        'marked_as_received_by' => 'integer',
        'marked_as_received_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'sales_receipt_uploaded_by' => 'integer',
    ];

    protected $sortable = [
        'chargebacks.case_number',
        'chargebacks.network_case_number',
        'chargebacks.reference_number',
        'company.store_number',
        'chargebacks.reason_code',
        'chargebacks.amount',
        'chargebacks.card_network',
        'chargebacks.transaction_date',
        'chargebacks.chargeback_received_date',
        'chargebacks.processor_due_date',
        'chargebacks.marked_as_received',
        'chargebacks.credited_date',
        'chargebacks.submitted',
        'chargebacks.submitted_at',
        'chargebacks.marked_as_received_at',
        'chargebacks.created_at',
        'chargebacks.updated_at',
    ];

    protected $searchable = [
        'case_number',
        'network_case_number',
        'reference_number',
        'company_id',
        'reason_code',
        'card_network',
        'card_last_four',
    ];

    protected $searchableColumns = [
        'case_number',
        'network_case_number',
        'reference_number',
        'card_last_four',
        'amount',
    ];

    protected $allowedFilters = [
        'case_number',
        'network_case_number',
        'reference_number',
        'company_id',
        'reason_code',
        'amount',
        'card_network',
        'card_last_four',
        'entry_mode',
        'transaction_date',
        'chargeback_received_date',
        'processor_due_date',
        'marked_as_received',
        'credited_date',
        'submitted',
        'submitted_at',
        'marked_as_received_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'sales_receipt_uploaded_by',
        'submitted_by',
        'marked_as_received_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number, state_id,workgroup_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(UploadDocument::class, 'document_id');
    }

    public function salesReceiptDocument(): BelongsTo
    {
        return $this->belongsTo(UploadDocument::class, 'sales_receipt_document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    public function salesReceiptUploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_receipt_uploaded_by')->select('id', 'name');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by')->select('id', 'name');
    }

    public function markedAsReceivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_as_received_by')->select('id', 'name');
    }

    public function hasSalesReceipt(): bool
    {
        return !empty($this->sales_receipt_document_id);
    }

    public function isPastDue(): bool
    {
        if (!$this->processor_due_date) {
            return false;
        }

        return Carbon::parse($this->processor_due_date)->lt(Carbon::today());
    }

    public function isReceiptUploadedLate(): bool
    {
        if (!$this->upload_sales_receipt_date || !$this->processor_due_date) {
            return false;
        }

        return Carbon::parse($this->upload_sales_receipt_date)->gt(Carbon::parse($this->processor_due_date));
    }

    public function getDisplayStatusAttribute(): array
    {
        if ($this->marked_as_received) {
            return [
                'key' => 'credited',
                'label' => 'Credited',
                'tone' => 'success',
                'sublabel' => null,
            ];
        }

        if ($this->submitted) {
            return [
                'key' => 'submitted',
                'label' => 'Submitted to Processor',
                'tone' => 'purple',
                'sublabel' => null,
            ];
        }

        if ($this->hasSalesReceipt()) {
            $uploadDate = $this->upload_sales_receipt_date ? Carbon::parse($this->upload_sales_receipt_date)->format('d F Y') : '';
            $isLate = $this->isReceiptUploadedLate();

            return [
                'key' => $isLate ? 'uploaded_late' : 'uploaded',
                'label' => "Uploaded {$uploadDate}",
                'tone' => $isLate ? 'warning' : 'info',
                'sublabel' => $isLate ? '⚠ after due date' : null,
            ];
        }

        if ($this->isPastDue()) {
            return [
                'key' => 'expired',
                'label' => 'Expired',
                'tone' => 'danger',
                'sublabel' => null,
            ];
        }

        return [
            'key' => 'receipt_pending',
            'label' => 'Receipt Pending',
            'tone' => 'warning',
            'sublabel' => null,
        ];
    }

    protected static function booted(): void
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
            $model->updated_by = Auth::id();
        });
    }

    protected function shouldLogActivity(): bool
    {
        return ! $this->skipActivityLog;
    }

    protected function getLoggableAttributesList(): ?array
    {
        return [
            'case_number',
            'network_case_number',
            'reference_number',
            'company_id',
            'reason_code',
            'amount',
            'card_network',
            'card_last_four',
            'entry_mode',
            'transaction_date',
            'chargeback_received_date',
            'processor_due_date',
            'notes',
            'document_id',
            'sales_receipt_document_id',
            'upload_sales_receipt_date',
            'sales_receipt_uploaded_by',
            'marked_as_received',
            'marked_as_received_by',
            'marked_as_received_at',
            'credited_date',
            'submitted',
            'submitted_by',
            'submitted_at',
            'created_by',
            'updated_by',
        ];
    }
}
