<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class LedgerDetails extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'ledger_details';

    protected $fillable = [
        'code',
        'ledger_id',
        'company_id',
        'starting_check_number',
        'default_bank',
        'transition_code',
        'account_type',
        'account_no',
        'routing',
        'bank_name',
        'bank_address',
    ];  

    protected $sortable = [
        'code',
        'account_type',
        'account_no',
        'bank_name',
        'bank_address',
        'created_at',
        'updated_at',
    ];

    protected $activityFields = [
        ['field' => 'code', 'label' => 'Code', 'type' => 'string'],
        ['field' => 'ledger_id', 'label' => 'Ledger', 'table' => 'ledgers', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'starting_check_number', 'label' => 'Starting Check Number', 'type' => 'string'],
        ['field' => 'default_bank', 'label' => 'Default Bank', 'type' => 'string'],
        ['field' => 'transition_code', 'label' => 'Transition Code', 'type' => 'string'],
        ['field' => 'account_type', 'label' => 'Account Type', 'type' => 'number'],
        ['field' => 'account_no', 'label' => 'Account No', 'type' => 'number'],
        ['field' => 'routing', 'label' => 'Routing', 'type' => 'string'],
        ['field' => 'bank_name', 'label' => 'Bank Name', 'type' => 'string'],
        ['field' => 'bank_address', 'label' => 'Bank Address', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $searchable = [
        'code',
        'account_type',
        'account_no',
        'bank_name',
        'bank_address',
        'created_at',
        'updated_at',
    ];

    protected $allowedFilters = [
        'account_type',
        'account_no',
        'bank_name',
        'bank_address',
        'starting_check_number',
        'default_bank',
        'created_at',
        'updated_at',
    ];

    public const ACCOUNT_TYPE_GENERAL = 'General';
    public const ACCOUNT_TYPE_BANK = 'Bank';

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
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
