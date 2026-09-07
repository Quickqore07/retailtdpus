<?php

namespace App\Models\Onboarding;

use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Support\AuthorizedCompanies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class EmployeeRateRequest extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies,LogsActivity;
    protected $table = 'employee_rate_requests';
    protected $fillable = [
        'employee_rate_id', 
        'employee_id', 
        'company_id',
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
        'till_date', 
        'payroll_hours',
        'payroll_hours_type',
        'check_payment_type',
        'check_payment_amount',
        'status',
        'notes',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'hr_approved',
        'hr_approved_at',
        'hr_approved_by',
        'do_approved',
        'do_approved_at',
        'do_approved_by',
        'admin_approved',
        'admin_approved_at',
        'admin_approved_by',   
    ];

    protected $activityFields = [
        ['field' => 'employee_rate_id', 'label' => 'Employee Rate', 'table' => 'employee_rates', 'table_field' => 'id', 'type' => 'lookup'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'pay_type', 'label' => 'Pay Type', 'type' => 'number'],
        ['field' => 'rate_type', 'label' => 'Rate Type', 'type' => 'number'],
        ['field' => 'payroll_type', 'label' => 'Payroll Type', 'type' => 'number'],
        ['field' => 'rate', 'label' => 'Rate', 'type' => 'number'],
        ['field' => 'slab_first_hours', 'label' => 'Slab First Hours', 'type' => 'number'],
        ['field' => 'slab_rest_rate', 'label' => 'Slab Rest Rate', 'type' => 'number'],
        ['field' => 'payroll_rate', 'label' => 'Payroll Rate', 'type' => 'number'],
        ['field' => 'ten99_rate', 'label' => 'Ten99 Rate', 'type' => 'number'],
        ['field' => 'effective_date', 'label' => 'Effective Date', 'type' => 'date'],
        ['field' => 'till_date', 'label' => 'Till Date', 'type' => 'date'],
        ['field' => 'payroll_hours', 'label' => 'Payroll Hours', 'type' => 'number'],
        ['field' => 'payroll_hours_type', 'label' => 'Payroll Hours Type', 'type' => 'number'],
        ['field' => 'check_payment_type', 'label' => 'Check Payment Type', 'type' => 'number'],
        ['field' => 'check_payment_amount', 'label' => 'Check Payment Amount', 'type' => 'number'],
        ['field' => 'status', 'label' => 'Status', 'type' => 'string'],
        ['field' => 'notes', 'label' => 'Notes', 'type' => 'string'],
        ['field' => 'rejection_reason', 'label' => 'Rejection Reason', 'type' => 'string'],
        ['field' => 'approved_by', 'label' => 'Approved By', 'type' => 'string'],
        ['field' => 'approved_at', 'label' => 'Approved At', 'type' => 'date'],
        ['field' => 'rejected_by', 'label' => 'Rejected By', 'type' => 'string'],
        ['field' => 'rejected_at', 'label' => 'Rejected At', 'type' => 'date'],
        ['field' => 'hr_approved', 'label' => 'Hr Approved', 'type' => 'boolean'],
        ['field' => 'hr_approved_at', 'label' => 'Hr Approved At', 'type' => 'date'],
        ['field' => 'hr_approved_by', 'label' => 'Hr Approved By', 'type' => 'string'],
        ['field' => 'do_approved', 'label' => 'Do Approved', 'type' => 'boolean'],
        ['field' => 'do_approved_at', 'label' => 'Do Approved At', 'type' => 'date'],
        ['field' => 'do_approved_by', 'label' => 'Do Approved By', 'type' => 'string'],
        ['field' => 'admin_approved', 'label' => 'Admin Approved', 'type' => 'boolean'],
        ['field' => 'admin_approved_at', 'label' => 'Admin Approved At', 'type' => 'date'],
        ['field' => 'admin_approved_by', 'label' => 'Admin Approved By', 'type' => 'string'],
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
        'employee_rate_id',
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
        'till_date',
        'payroll_hours',
        'check_payment_type',
        'check_payment_amount',
        'created_at',
        'updated_at',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'company_id',

        'hr_approved',
        'hr_approved_at',
        'hr_approved_by',
        'do_approved',
        'do_approved_at',
        'do_approved_by',
        'admin_approved',
        'admin_approved_at',
        'admin_approved_by',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'employee_rate_id',
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
        'till_date',
        'payroll_hours',
        'check_payment_type',
        'check_payment_amount',
        'company_id',

        'hr_approved',
        'hr_approved_at',
        'hr_approved_by',
        'do_approved',
        'do_approved_at',
        'do_approved_by',
        'admin_approved',
        'admin_approved_at',
        'admin_approved_by',
        'created_at',
        'updated_at',
    ];
    protected $allowedFilters = [
        'employee_rate_id',
        'employee_id',
        'role_id',
        'pay_type',
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
        'company_id',
        'till_date',

        'hr_approved',
        'hr_approved_at',
        'hr_approved_by',
        'do_approved',
        'do_approved_at',
        'do_approved_by',
        'admin_approved',
        'admin_approved_at',
        'admin_approved_by',
        'created_at',
        'updated_at',
    ];
    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id')->select('id', 'name', 'code');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->select('id', 'employee_id', 'pos_name', 'mail_sent', 'onboarding_status', 'workgroup_id','employee_type');
    }
    public function employeeRate()
    {
        return $this->belongsTo(EmployeeRates::class, 'employee_rate_id')->select('id', 'employee_id', 'role_id', 'rate', 'payroll_hours', 'payroll_hours_type', 'pay_type', 'slab_first_hours', 'slab_rest_rate', 'check_payment_type', 'check_payment_amount', 'rate_type','effective_date','company_id', 'till_date', 'ten99_rate')->with('role','company');
    }
    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'name', 'state_id','store_number')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id,name as company_name');
    }

    public function hrApprovedBy()
    {
        return $this->belongsTo(User::class, 'hr_approved_by')->select('id', 'name', 'email')->select('id', 'name', 'email');
    }

    public function doApprovedBy()
    {
        return $this->belongsTo(User::class, 'do_approved_by')->select('id', 'name', 'email')->select('id', 'name', 'email');
    }

    public function adminApprovedBy()
    {
        return $this->belongsTo(User::class, 'admin_approved_by')->select('id', 'name', 'email')->select('id', 'name', 'email');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by')->select('id', 'name', 'email')->select('id', 'name', 'email');
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