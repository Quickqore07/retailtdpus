<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivity;

class DigitalSignature extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'digital_signature';

    protected $fillable = [
        'onboarding_list_id',
        'employee_id',
        'employee_name',
        'signature',
        'date',
    ];

    protected $activityFields = [
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'employee_name', 'label' => 'Employee Name', 'type' => 'string'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id');
    }
}
