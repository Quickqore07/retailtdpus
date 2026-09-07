<?php

namespace App\Models\Payroll;

use App\Models\Employee;
use App\Models\Onboarding\EmployeeHoursRequest;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Support\AuthorizedCompanies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\LogsActivity;

class EmployeeHours extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;
    
    protected $table = 'employee_hours';
    
    protected $fillable = [
        'date',
        'employee_id',
        'employee_name',
        'company_id',
        'dev_id',
        'ssn',
        'pay_id',
        'pay_type',
        'total_hours',
        'tips',
        'mileage_excess',
        'incentive',
        'bonus',
        'home_store',
        'role_id',
        'pay_rate',
        'tips_due',
        'mileage_due',
    ];

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'employee_name', 'label' => 'Employee Name', 'type' => 'string'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'dev_id', 'label' => 'Dev Id', 'type' => 'string'],
        ['field' => 'ssn', 'label' => 'Ssn', 'type' => 'string'],
        ['field' => 'pay_id', 'label' => 'Pay Id', 'type' => 'number'],
        ['field' => 'pay_type', 'label' => 'Pay Type', 'type' => 'number'],
        ['field' => 'total_hours', 'label' => 'Total Hours', 'type' => 'number'],
        ['field' => 'tips', 'label' => 'Tips', 'type' => 'number'],
        ['field' => 'mileage_excess', 'label' => 'Mileage Excess', 'type' => 'number'],
        ['field' => 'incentive', 'label' => 'Incentive', 'type' => 'string'],
        ['field' => 'bonus', 'label' => 'Bonus', 'type' => 'string'],
        ['field' => 'home_store', 'label' => 'Home Store', 'type' => 'string'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'pay_rate', 'label' => 'Pay Rate', 'type' => 'number'],
        ['field' => 'tips_due', 'label' => 'Tips Due', 'type' => 'number'],
        ['field' => 'mileage_due', 'label' => 'Mileage Due', 'type' => 'number'],
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
        'total_hours' => 'float',
        'tips' => 'float',
        'mileage_excess' => 'float',
        'incentive' => 'float',
        'bonus' => 'float',
        'pay_rate' => 'float',
        'tips_due' => 'float',
        'mileage_due' => 'float',
    ];
    protected $searchableColumns = [
        'date',
        'employee_name',
        'ssn',
        'total_hours',
        'tips',
        'mileage_excess',
    ];
    protected $sortable = [
        'date',
        'employee_name',
        'total_hours',
        'pay_rate',
        'created_at',
        'updated_at',
        'company.name',
        'tips',
        'mileage_excess',
        'incentive',
        'bonus',
        'tips_due',
        'mileage_due',
    ];

    protected $searchable = [
        'date',
        'employee_name',
        'ssn',
        'company.name',
        'total_hours',
        'tips',
        'mileage_excess',
        'incentive',
        'bonus',
        'tips_due',
        'mileage_due',
    ];

    protected $allowedFilters = [
        'date',
        'employee_id',
        'company_id',
        'role_id',
        'pay_type',
        'company.name',
        'total_hours',
        'tips',
        'mileage_excess',
        'incentive',
        'bonus',
        'tips_due',
        'mileage_due',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->with('employeeRates')->select('id', 'employee_id', 'ssn','pos_name','employee_type');
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'name', 'state_id','store_number')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id')->select('id', 'name', 'code');
    }

    public function employeeHoursRequest()
    {
        return $this->hasOne(EmployeeHoursRequest::class, 'employee_hours_id');
    }


}

