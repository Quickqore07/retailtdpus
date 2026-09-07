<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivity;

class FinalSubmission extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'final_submission';

    protected $fillable = [
        'onboarding_list_id',
        'employee_id',
        'check_confirmation',
        'certification_complete',
        'work_authorization_confirmed',
        'false_info_acknowledgment',
        'electronic_signature_consent',
        'signature',
        'signature_date',
    ];

    protected $activityFields = [
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'check_confirmation', 'label' => 'Check Confirmation', 'type' => 'string'],
        ['field' => 'certification_complete', 'label' => 'Certification Complete', 'type' => 'string'],
        ['field' => 'work_authorization_confirmed', 'label' => 'Work Authorization Confirmed', 'type' => 'string'],
        ['field' => 'false_info_acknowledgment', 'label' => 'False Info Acknowledgment', 'type' => 'string'],
        ['field' => 'electronic_signature_consent', 'label' => 'Electronic Signature Consent', 'type' => 'string'],
        ['field' => 'signature_date', 'label' => 'Signature Date', 'type' => 'date'],
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
        'check_confirmation' => 'boolean',
        'certification_complete' => 'boolean',
        'work_authorization_confirmed' => 'boolean',
        'false_info_acknowledgment' => 'boolean',
        'electronic_signature_consent' => 'boolean',
        'signature_date' => 'date',
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
