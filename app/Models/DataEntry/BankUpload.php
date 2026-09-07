<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Company;
use App\Models\Settings\Ledger;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BankUpload extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'bank_uploads';

    protected $fillable = [
        'company_id',
        'account_number',
        'bank_id',
        'description',
        'amount',
        'date',
        'is_opening_balance',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'account_number', 'label' => 'Account Number', 'type' => 'number'],
        ['field' => 'bank_id', 'label' => 'Bank', 'table' => 'ledgers', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'description', 'label' => 'Description', 'type' => 'string'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'is_opening_balance', 'label' => 'Is Opening Balance', 'type' => 'boolean'],
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
        'account_number',
        'description',
        'amount',
        'date',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'bank_id' => 'integer',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'company.store_number',
        'date',
        'account_number',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'company_id',
        'account_number',
        'description',
        'amount',
        'date',
    ];

    protected $allowedFilters = [
        'company_id',
        'account_number',
        'bank_id',
        'description',
        'amount',
        'date',
        'created_at',
        'updated_at',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number, state_id');
    }

    public function bank()
    {
        return $this->belongsTo(Ledger::class, 'bank_id')->select('id', 'name');
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
}
