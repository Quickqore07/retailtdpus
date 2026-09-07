<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;

class FormW4 extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'w4_forms';

    protected $fillable = [
        'employee_id',
        'onboarding_list_id',
        'single_or_married',
        'married_filing',
        'head_of_household',
        'multiple_jobs_and_spouse_works',
        'qualifying_children',
        'dependents',
        'total_amount',
        'other_income',
        'deductions',
        'extra_withholding',
        'worksheet_line1',
        'worksheet_line2a',
        'worksheet_line2b',
        'worksheet_line2c',
        'worksheet_line3',
        'worksheet_line4',
        'worksheet_ded1',
        'worksheet_ded2',
        'worksheet_ded3',
        'worksheet_ded4',
        'worksheet_ded5',
        'employee_sign',
        'employee_date',
        'employer_name',
        'date_of_employment',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'single_or_married', 'label' => 'Single Or Married', 'type' => 'string'],
        ['field' => 'married_filing', 'label' => 'Married Filing', 'type' => 'string'],
        ['field' => 'head_of_household', 'label' => 'Head Of Household', 'type' => 'string'],
        ['field' => 'multiple_jobs_and_spouse_works', 'label' => 'Multiple Jobs And Spouse Works', 'type' => 'string'],
        ['field' => 'qualifying_children', 'label' => 'Qualifying Children', 'type' => 'string'],
        ['field' => 'dependents', 'label' => 'Dependents', 'type' => 'string'],
        ['field' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number'],
        ['field' => 'other_income', 'label' => 'Other Income', 'type' => 'string'],
        ['field' => 'deductions', 'label' => 'Deductions', 'type' => 'string'],
        ['field' => 'extra_withholding', 'label' => 'Extra Withholding', 'type' => 'string'],
        ['field' => 'worksheet_line1', 'label' => 'Worksheet Line1', 'type' => 'string'],
        ['field' => 'worksheet_line2a', 'label' => 'Worksheet Line2A', 'type' => 'string'],
        ['field' => 'worksheet_line2b', 'label' => 'Worksheet Line2B', 'type' => 'string'],
        ['field' => 'worksheet_line2c', 'label' => 'Worksheet Line2C', 'type' => 'string'],
        ['field' => 'worksheet_line3', 'label' => 'Worksheet Line3', 'type' => 'string'],
        ['field' => 'worksheet_line4', 'label' => 'Worksheet Line4', 'type' => 'string'],
        ['field' => 'worksheet_ded1', 'label' => 'Worksheet Ded1', 'type' => 'string'],
        ['field' => 'worksheet_ded2', 'label' => 'Worksheet Ded2', 'type' => 'string'],
        ['field' => 'worksheet_ded3', 'label' => 'Worksheet Ded3', 'type' => 'string'],
        ['field' => 'worksheet_ded4', 'label' => 'Worksheet Ded4', 'type' => 'string'],
        ['field' => 'worksheet_ded5', 'label' => 'Worksheet Ded5', 'type' => 'string'],
        ['field' => 'employee_date', 'label' => 'Employee Date', 'type' => 'date'],
        ['field' => 'employer_name', 'label' => 'Employer Name', 'type' => 'string'],
        ['field' => 'date_of_employment', 'label' => 'Date Of Employment', 'type' => 'string'],
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
        'employee_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }
}
