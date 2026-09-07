<?php

namespace App\Models\DataEntry;

use App\Models\Employee;
use App\Models\Settings\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedCompanies;

class Drivers extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedCompanies;

    protected $table = 'drivers';

    protected $fillable = [
        'date',
        'company_id',
        'driver_id',
        'driver_name',
        'employee_id',
        'time_in',
        'time_out',
        'driver_in_store_pay',
        'other_in_store_pay',
        'on_road_pay',
        'cash_tips',
        'cc_tips',
        'mileage',
        'all_in_pay',
        'store_hours',
        'road_hours',
        'total_hours',
        'avg_pay_per_hour',
        'delivery',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'driver_id', 'label' => 'Driver Id', 'type' => 'string'],
        ['field' => 'driver_name', 'label' => 'Driver Name', 'type' => 'string'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'time_in', 'label' => 'Time In', 'type' => 'string'],
        ['field' => 'time_out', 'label' => 'Time Out', 'type' => 'string'],
        ['field' => 'driver_in_store_pay', 'label' => 'Driver In Store Pay', 'type' => 'number'],
        ['field' => 'other_in_store_pay', 'label' => 'Other In Store Pay', 'type' => 'number'],
        ['field' => 'on_road_pay', 'label' => 'On Road Pay', 'type' => 'number'],
        ['field' => 'cash_tips', 'label' => 'Cash Tips', 'type' => 'number'],
        ['field' => 'cc_tips', 'label' => 'Cc Tips', 'type' => 'number'],
        ['field' => 'mileage', 'label' => 'Mileage', 'type' => 'number'],
        ['field' => 'all_in_pay', 'label' => 'All In Pay', 'type' => 'number'],
        ['field' => 'store_hours', 'label' => 'Store Hours', 'type' => 'number'],
        ['field' => 'road_hours', 'label' => 'Road Hours', 'type' => 'number'],
        ['field' => 'total_hours', 'label' => 'Total Hours', 'type' => 'number'],
        ['field' => 'avg_pay_per_hour', 'label' => 'Avg Pay Per Hour', 'type' => 'number'],
        ['field' => 'delivery', 'label' => 'Delivery', 'type' => 'number'],
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
        'date',
        'driver_id',
        'driver_name',
        'employee_id',
        'time_in',
        'time_out',
        'cash_tips',
        'mileage',
    ];
    protected $casts = [
        'company_id' => 'integer',
        'driver_id' => 'string',
        'driver_name' => 'string',
        'employee_id' => 'integer',
        'cash_tips' => 'decimal:2',
        'cc_tips' => 'decimal:2',
        'mileage' => 'decimal:2',
        'all_in_pay' => 'decimal:2',
        'store_hours' => 'decimal:2',
        'road_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'avg_pay_per_hour' => 'decimal:2',
        'delivery' => 'integer',
        'driver_in_store_pay' => 'decimal:2',
        'other_in_store_pay' => 'decimal:2',
        'on_road_pay' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $sortable = [
        'date',
        'company.name',
        'driver_id',
        'driver_name',
        'employee_id',
        'time_in',
        'time_out',
        'cash_tips',
        'mileage',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'date',
        'company.name',
        'driver_id',
        'driver_name',
        'employee_id',
        'time_in',
        'time_out',
        'cash_tips',
        'mileage',
    ];

    protected $allowedFilters = [
        'date',
        'company_id',
        'driver_id',
        'driver_name',
        'employee_id',
        'time_in',
        'time_out',
        'cash_tips',
        'mileage',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->select('id',  'employee_id', 'pos_name');
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