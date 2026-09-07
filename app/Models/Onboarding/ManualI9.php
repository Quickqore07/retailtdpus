<?php

namespace App\Models\Onboarding;

use App\Models\Employee;
use App\Models\Settings\Company;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\AuthorizedWorkgroup;

class ManualI9 extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedWorkgroup;

    protected $table = 'manual_i9';

    protected $fillable = [
        'employee_id',
        'company_id',
        'left_terminate',
        'left_note',
        'left_at',
        'left_updated_by',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'left_terminate', 'label' => 'Left Terminate', 'type' => 'boolean'],
        ['field' => 'left_note', 'label' => 'Left Note', 'type' => 'string'],
        ['field' => 'left_at', 'label' => 'Left At', 'type' => 'date'],
        ['field' => 'left_updated_by', 'label' => 'Left Updated By', 'type' => 'string'],
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
        'left_terminate' => 'boolean',
        'left_at' => 'datetime',
    ];

    protected $sortable = [
        'employee_name',
        'company_name',
        'left_terminate',
        'left_at',
        'created_at',
        'updated_at',
        'i9_uploaded',
    ];

    protected $allowedFilters = [
        'employee.id',
        'company.id',
        'employeeConfirmation.hr_status',
        'left_terminate',
        'left_at',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'employee.pos_name',
        'employee.employee_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function employeeConfirmation()
    {
        return $this->hasOne(EmployeeConfirmation::class, 'employee_id', 'employee_id');
    }

    public function leftUpdatedBy()
    {
        return $this->belongsTo(User::class, 'left_updated_by');
    }
}
