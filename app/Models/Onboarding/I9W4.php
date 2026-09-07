<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Models\Settings\Workgroup;
use Illuminate\Support\Facades\Auth;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;

class I9W4 extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'i9_w4';

    protected $fillable = [
        'employee_id',
        'onboarding_list_id',
        // Profile Information
        'type',
        'email',
        'first_name',
        'middle_name',
        'last_name',
        'street',
        'apt',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'gender',
        'birthdate',
        'ssn',
        'preferred_name',
        // Groups
        'workgroup_id',
        'workgroup_name',
        // Employment
        'job_title',
        'employment_type',
        'start_date',
        'manager_id',
        'manager',
        'salary',
        'salary_period',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'type', 'label' => 'Type', 'type' => 'string'],
        ['field' => 'email', 'label' => 'Email', 'type' => 'string'],
        ['field' => 'first_name', 'label' => 'First Name', 'type' => 'string'],
        ['field' => 'middle_name', 'label' => 'Middle Name', 'type' => 'string'],
        ['field' => 'last_name', 'label' => 'Last Name', 'type' => 'string'],
        ['field' => 'street', 'label' => 'Street', 'type' => 'string'],
        ['field' => 'apt', 'label' => 'Apt', 'type' => 'string'],
        ['field' => 'city', 'label' => 'City', 'type' => 'string'],
        ['field' => 'state', 'label' => 'State', 'type' => 'string'],
        ['field' => 'zip', 'label' => 'Zip', 'type' => 'string'],
        ['field' => 'country', 'label' => 'Country', 'type' => 'number'],
        ['field' => 'phone', 'label' => 'Phone', 'type' => 'string'],
        ['field' => 'gender', 'label' => 'Gender', 'type' => 'string'],
        ['field' => 'birthdate', 'label' => 'Birthdate', 'type' => 'string'],
        ['field' => 'ssn', 'label' => 'Ssn', 'type' => 'string'],
        ['field' => 'preferred_name', 'label' => 'Preferred Name', 'type' => 'string'],
        ['field' => 'workgroup_id', 'label' => 'Workgroup', 'table' => 'workgroup', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'workgroup_name', 'label' => 'Workgroup Name', 'type' => 'string'],
        ['field' => 'job_title', 'label' => 'Job Title', 'type' => 'string'],
        ['field' => 'employment_type', 'label' => 'Employment Type', 'type' => 'string'],
        ['field' => 'start_date', 'label' => 'Start Date', 'type' => 'date'],
        ['field' => 'manager_id', 'label' => 'Manager Id', 'type' => 'string'],
        ['field' => 'manager', 'label' => 'Manager', 'type' => 'string'],
        ['field' => 'salary', 'label' => 'Salary', 'type' => 'number'],
        ['field' => 'salary_period', 'label' => 'Salary Period', 'type' => 'number'],
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
        // 'birthdate' => 'date',
        // 'start_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }

    public function workgroup()
    {
        return $this->belongsTo(Workgroup::class, 'workgroup_id');
    }

    public function managerEmployee()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
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
