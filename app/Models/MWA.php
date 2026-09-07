<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;  
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;

class MWA extends Model
{
    use HasFactory, LogsActivity, AuthorizedCompanies, Filterable;

    protected $table = 'mwa';

    protected $fillable = [
        'employee_id',
        'company_id',
        'role_id',
        'eow',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'eow', 'label' => 'Week Ending', 'type' => 'date'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
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
    ];

    protected $sortable = [
        'eow',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'eow',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'eow',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $allowedFilters = [
        'eow',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $allowedSorts = [
        'eow',
        'amount',
        'created_at',
        'updated_at',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id');
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
