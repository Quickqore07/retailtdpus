<?php

namespace App\Models\Payroll;

use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\AuthorizedCompanies;
use App\Models\Traits\LogsActivity;
class EmployeeRates extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;
    protected $table = 'employee_rates';
    protected $fillable = [
        'employee_id',
        'payroll_type', 
        'role_id', 
        'pay_type', 
        'rate_type', 
        'rate', 
        'slab_first_hours', 
        'slab_rest_rate', 
        'payroll_rate', 
        'ten99_rate', 
        'effective_date',
        'payroll_hours',
        'payroll_hours_type',
        'check_payment_type',
        'check_payment_amount',
        'company_id',
        'till_date'
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'payroll_type', 'label' => 'Payroll Type', 'type' => 'number'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'pay_type', 'label' => 'Pay Type', 'type' => 'number'],
        ['field' => 'rate_type', 'label' => 'Rate Type', 'type' => 'number'],
        ['field' => 'rate', 'label' => 'Rate', 'type' => 'number'],
        ['field' => 'slab_first_hours', 'label' => 'Slab First Hours', 'type' => 'number'],
        ['field' => 'slab_rest_rate', 'label' => 'Slab Rest Rate', 'type' => 'number'],
        ['field' => 'payroll_rate', 'label' => 'Payroll Rate', 'type' => 'number'],
        ['field' => 'ten99_rate', 'label' => 'Ten99 Rate', 'type' => 'number'],
        ['field' => 'effective_date', 'label' => 'Effective Date', 'type' => 'date'],
        ['field' => 'payroll_hours', 'label' => 'Payroll Hours', 'type' => 'number'],
        ['field' => 'payroll_hours_type', 'label' => 'Payroll Hours Type', 'type' => 'number'],
        ['field' => 'check_payment_type', 'label' => 'Check Payment Type', 'type' => 'number'],
        ['field' => 'check_payment_amount', 'label' => 'Check Payment Amount', 'type' => 'number'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'till_date', 'label' => 'Till Date', 'type' => 'date'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $sortable = [
        'employee_id',
        'role_id',
        'pay_type',
        'rate_type',
        'payroll_type',
        'rate',
        'slab_first_hours',
        'slab_rest_rate',
        'payroll_rate',
        'ten99_rate',
        'effective_date',
        'payroll_hours',
        'payroll_hours_type',
        'check_payment_type',
        'check_payment_amount',
        'company_id',
        'till_date',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'employee_id',
        'role_id',
        'pay_type',
        'company_id',
        'rate_type',
        'payroll_type',
        'rate',
        'slab_first_hours',
        'slab_rest_rate',
        'ten99_rate',
        'effective_date',
        'payroll_hours',
        'check_payment_type',
        'check_payment_amount',
        'till_date',
    ];
    protected $allowedFilters = [
        'employee_id',
        'role_id',
        'pay_type',
        'company_id',
        'rate_type',
        'payroll_type',
        'rate',
        'slab_first_hours',
        'slab_rest_rate',
        'ten99_rate',
        'effective_date',
        'payroll_hours',
        'check_payment_type',
        'check_payment_amount',
        'till_date',
    ];
    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id')->select('id', 'name', 'code');
    }
    public function employeeRatesRequest()
    {
        return $this->hasOne(EmployeeRateRequest::class, 'employee_rate_id')->with('role','hrApprovedBy','doApprovedBy','adminApprovedBy')->latest();
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->select('id', 'name', 'state_id','store_number')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }
}

