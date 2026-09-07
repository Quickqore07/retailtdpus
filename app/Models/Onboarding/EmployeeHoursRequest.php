<?php

namespace App\Models\Onboarding;

use App\Models\Employee;
use App\Models\Payroll\EmployeeHours;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Support\AuthorizedCompanies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class EmployeeHoursRequest extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'employee_hours_requests';

    protected $fillable = [
        'employee_hours_id',
        'employee_id',
        'company_id',
        'role_id',
        'date',
        'employee_name',
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
        'pay_rate',
        'tips_due',
        'mileage_due',
        'status',
        'notes',
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
        ['field' => 'employee_hours_id', 'label' => 'Employee Hours Id', 'type' => 'number'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'employee_name', 'label' => 'Employee Name', 'type' => 'string'],
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
        ['field' => 'pay_rate', 'label' => 'Pay Rate', 'type' => 'number'],
        ['field' => 'tips_due', 'label' => 'Tips Due', 'type' => 'number'],
        ['field' => 'mileage_due', 'label' => 'Mileage Due', 'type' => 'number'],
        ['field' => 'status', 'label' => 'Status', 'type' => 'string'],
        ['field' => 'notes', 'label' => 'Notes', 'type' => 'string'],
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

    protected $sortable = [
        'date',
        'employee_id',
        'company_id',
        'role_id',
        'total_hours',
        'pay_rate',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'date',
        'employee_name',
        'ssn',
    ];

    protected $allowedFilters = [
        'date',
        'employee_id',
        'company_id',
        'role_id',
        'pay_type',
        'status',
        'created_at',
        'updated_at',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->select('id', 'employee_id', 'pos_name');
    }

    public function employeeHour()
    {
        return $this->belongsTo(EmployeeHours::class, 'employee_hours_id')
            ->select('id', 'employee_id', 'company_id', 'role_id', 'date', 'pay_type', 'total_hours', 'pay_rate', 'tips', 'tips_due', 'mileage_excess', 'mileage_due')
            ->with('role');
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'name', 'state_id', 'store_number');
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id')->select('id', 'name', 'code');
    }

    public function hrApprovedBy()
    {
        return $this->belongsTo(User::class, 'hr_approved_by')->select('id', 'name', 'email');
    }

    public function doApprovedBy()
    {
        return $this->belongsTo(User::class, 'do_approved_by')->select('id', 'name', 'email');
    }

    public function adminApprovedBy()
    {
        return $this->belongsTo(User::class, 'admin_approved_by')->select('id', 'name', 'email');
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
