<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Models\Traits\LogsActivity;
class BenefitEnrollment extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'benefit_enrollments';

    protected $fillable = [
        'onboarding_list_id',
        'employee_id',
        'gender',
        'salary',
        'hire_date',
        'medical_plan',
        'medical_waive_reason',
        'dental',
        'vision',
        'life_option',
        'emp_life_amount',
        'spouse_life_amount',
        'child_life_amount',
        'emp_life_cost',
        'spouse_life_cost',
        'child_life_cost',
        'tax_method',
        'employee_sign',
        'employee_date',
        'benefit_acknowledgment',
        'waive_ack_point_1',
        'waive_ack_point_2',
        'waive_ack_name',
        'waive_ack_signature',
        'waive_ack_date',
    ];

    protected $activityFields = [
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'gender', 'label' => 'Gender', 'type' => 'string'],
        ['field' => 'salary', 'label' => 'Salary', 'type' => 'number'],
        ['field' => 'hire_date', 'label' => 'Hire Date', 'type' => 'date'],
        ['field' => 'medical_plan', 'label' => 'Medical Plan', 'type' => 'string'],
        ['field' => 'medical_waive_reason', 'label' => 'Medical Waive Reason', 'type' => 'string'],
        ['field' => 'dental', 'label' => 'Dental', 'type' => 'boolean'],
        ['field' => 'vision', 'label' => 'Vision', 'type' => 'boolean'],
        ['field' => 'life_option', 'label' => 'Life Option', 'type' => 'string'],
        ['field' => 'emp_life_amount', 'label' => 'Emp Life Amount', 'type' => 'number'],
        ['field' => 'spouse_life_amount', 'label' => 'Spouse Life Amount', 'type' => 'number'],
        ['field' => 'child_life_amount', 'label' => 'Child Life Amount', 'type' => 'number'],
        ['field' => 'emp_life_cost', 'label' => 'Emp Life Cost', 'type' => 'number'],
        ['field' => 'spouse_life_cost', 'label' => 'Spouse Life Cost', 'type' => 'number'],
        ['field' => 'child_life_cost', 'label' => 'Child Life Cost', 'type' => 'number'],
        ['field' => 'tax_method', 'label' => 'Tax Method', 'type' => 'string'],
        ['field' => 'employee_date', 'label' => 'Employee Date', 'type' => 'date'],
        ['field' => 'benefit_acknowledgment', 'label' => 'Benefit Acknowledgment', 'type' => 'string'],
        ['field' => 'waive_ack_point_1', 'label' => 'Waive Ack Point 1', 'type' => 'string'],
        ['field' => 'waive_ack_point_2', 'label' => 'Waive Ack Point 2', 'type' => 'string'],
        ['field' => 'waive_ack_name', 'label' => 'Waive Ack Name', 'type' => 'string'],
        ['field' => 'waive_ack_date', 'label' => 'Waive Ack Date', 'type' => 'date'],
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
        'hire_date' => 'date',
        'employee_date' => 'date',
        'waive_ack_date' => 'date',
        'waive_ack_point_1' => 'boolean',
        'waive_ack_point_2' => 'boolean',
        'salary' => 'decimal:2',
        'emp_life_amount' => 'decimal:2',
        'spouse_life_amount' => 'decimal:2',
        'child_life_amount' => 'decimal:2',
        'emp_life_cost' => 'decimal:2',
        'spouse_life_cost' => 'decimal:2',
        'child_life_cost' => 'decimal:2',
    ];

    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }

    public function dependents()
    {
        return $this->hasMany(BenefitEnrollmentDependent::class, 'benefit_enrollment_id')->orderBy('id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
