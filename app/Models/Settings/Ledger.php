<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class Ledger extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'ledgers';

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'code', 'label' => 'Code', 'type' => 'string'],
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'account_type', 'label' => 'Account Type', 'type' => 'string'],
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
        'name',
        'ledgers.account_type',
    ];

    protected $sortable = [
        'code',
        'name',
        'account_type',
        'account_no',
        'bank_name',
        'bank_address',
        'created_by',
        'updated_by',
        'created_at',
    ];

    protected $searchable = [
        'code',
        'name',
        'account_type',
        'account_no',
        'bank_name',
        'bank_address',
        'created_by',
        'updated_by',
        'created_at',
    ];

    protected $allowedFilters = [
        'code',
        'name',
        'account_type',
        'account_no',
        'bank_name',
        'bank_address',
        'created_by',
        'updated_by',
        'created_at',
    ];

    public function ledgerDetails()
    {
        return $this->hasOne(LedgerDetails::class);
    }
    public function check()
    {
        return $this->hasMany(CheckMaster::class);
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
