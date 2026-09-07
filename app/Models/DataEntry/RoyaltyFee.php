<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Company;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Services\FileUploadService;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RoyaltyFee extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    public bool $skipActivityLog = false;

    public const TYPE_ADVERTISEMENT = 'Advertisement';
    public const TYPE_ROYALTY = 'Royalty';

    protected $table = 'royalty_fees';

    protected $fillable = [
        'type',
        'company_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'description',
        'invoice_pdf',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'invoice_pdf_url',
        'invoice_pdf_name',
    ];

    protected $activityFields = [
        ['field' => 'type', 'label' => 'Type', 'type' => 'string'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'invoice_number', 'label' => 'Invoice Number', 'type' => 'string'],
        ['field' => 'invoice_date', 'label' => 'Invoice Date', 'type' => 'date'],
        ['field' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'description', 'label' => 'Description', 'type' => 'string'],
        ['field' => 'invoice_pdf', 'label' => 'Invoice PDF', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $searchableColumns = [
        'type',
        'invoice_number',
        'amount',
        'description',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'invoice_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'royalty_fees.type',
        'royalty_fees.invoice_number',
        'royalty_fees.invoice_date',
        'royalty_fees.due_date',
        'royalty_fees.amount',
        'company.store_number',
        'royalty_fees.created_at',
        'royalty_fees.updated_at',
    ];

    protected $searchable = [
        'type',
        'company_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'description',
    ];

    protected $allowedFilters = [
        'type',
        'company_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'description',
        'created_at',
        'updated_at',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_ADVERTISEMENT,
            self::TYPE_ROYALTY,
        ];
    }

    public static function normalizeInvoiceNumber(?string $invoiceNumber): string
    {
        $invoiceNumber = trim((string) $invoiceNumber);
        if ($invoiceNumber === '' || !preg_match('/^0+\d+$/', $invoiceNumber)) {
            return $invoiceNumber;
        }

        $stripped = ltrim($invoiceNumber, '0');

        return $stripped === '' ? '0' : $stripped;
    }

    public function setInvoiceNumberAttribute($value): void
    {
        $this->attributes['invoice_number'] = self::normalizeInvoiceNumber($value);
    }

    public function getInvoiceNumberAttribute($value): string
    {
        return self::normalizeInvoiceNumber($value);
    }

    public static function storageDirectory(string $type): string
    {
        return 'Fees/' . $type;
    }

    public function getInvoicePdfUrlAttribute(): ?string
    {
        if (empty($this->invoice_pdf)) {
            return null;
        }

        try {
            return app(FileUploadService::class)->url($this->invoice_pdf);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getInvoicePdfNameAttribute(): ?string
    {
        if (empty($this->invoice_pdf)) {
            return null;
        }

        $name = basename($this->invoice_pdf);
        if (preg_match('/^[a-f0-9]+_(.+)$/i', $name, $matches)) {
            $name = $matches[1];
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $stem = pathinfo($name, PATHINFO_FILENAME);
        $stem = self::normalizeInvoiceNumber($stem);

        return $extension ? $stem . '.' . $extension : $stem;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')
            ->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
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

    protected function shouldLogActivity(): bool
    {
        return ! $this->skipActivityLog;
    }
}
