<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Company;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WcEntry extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'wc_entries';

    protected $fillable = [
        'year',
        'eow',
        'company_id',
        'driver_pay',
        'non_driver_pay',
        'total_pay',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'year', 'label' => 'Year', 'type' => 'string'],
        ['field' => 'eow', 'label' => 'Eow', 'type' => 'string'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'driver_pay', 'label' => 'Driver Pay', 'type' => 'number'],
        ['field' => 'non_driver_pay', 'label' => 'Non Driver Pay', 'type' => 'number'],
        ['field' => 'total_pay', 'label' => 'Total Pay', 'type' => 'number'],
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
        'year',
        'eow',
        'driver_pay',
        'non_driver_pay',
        'total_pay',
    ];
    protected $casts = [
        'year' => 'integer',
        'company_id' => 'integer',
        'driver_pay' => 'decimal:2',
        'non_driver_pay' => 'decimal:2',
        'total_pay' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'wc_entries.year',
        'wc_entries.eow',
        'company.store_number',
        'wc_entries.driver_pay',
        'wc_entries.non_driver_pay',
        'wc_entries.total_pay',
        'wc_entries.created_at',
        'wc_entries.updated_at',
    ];

    protected $searchable = [
        'year',
        'eow',
        'company_id',
        'driver_pay',
        'non_driver_pay',
        'total_pay',
    ];

    protected $allowedFilters = [
        'year',
        'eow',
        'company_id',
        'driver_pay',
        'non_driver_pay',
        'total_pay',
        'created_at',
        'updated_at',
    ];

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
}
