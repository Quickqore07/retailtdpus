<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivity;

class EmergencyContact extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'emergency_contacts';

    protected $fillable = [
        'onboarding_list_id',
        'employee_id',
        'e_emergency_contact_first_name',
        'e_emergency_contact_last_name',
        'e_emergency_contact_relationship',
        'e_emergency_contact_phone_home',
        'e_emergency_contact_phone_cell',
        'e_emergency_contact_phone_work',
        'e_emergency_contact_email',
        'e_emergency_contact2_first_name',
        'e_emergency_contact2_last_name',
        'e_emergency_contact2_relationship',
        'e_emergency_contact2_phone_home',
        'e_emergency_contact2_phone_cell',
        'e_emergency_contact2_phone_work',
        'e_emergency_contact2_email',
        'e_emergency_contact2_hospital',
        'e_insurance_company',
        'e_insurance_policy_number',
        'e_signature',
        'e_signature_date',
    ];

    protected $activityFields = [
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'e_emergency_contact_first_name', 'label' => 'E Emergency Contact First Name', 'type' => 'string'],
        ['field' => 'e_emergency_contact_last_name', 'label' => 'E Emergency Contact Last Name', 'type' => 'string'],
        ['field' => 'e_emergency_contact_relationship', 'label' => 'E Emergency Contact Relationship', 'type' => 'string'],
        ['field' => 'e_emergency_contact_phone_home', 'label' => 'E Emergency Contact Phone Home', 'type' => 'string'],
        ['field' => 'e_emergency_contact_phone_cell', 'label' => 'E Emergency Contact Phone Cell', 'type' => 'string'],
        ['field' => 'e_emergency_contact_phone_work', 'label' => 'E Emergency Contact Phone Work', 'type' => 'string'],
        ['field' => 'e_emergency_contact_email', 'label' => 'E Emergency Contact Email', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_first_name', 'label' => 'E Emergency Contact2 First Name', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_last_name', 'label' => 'E Emergency Contact2 Last Name', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_relationship', 'label' => 'E Emergency Contact2 Relationship', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_phone_home', 'label' => 'E Emergency Contact2 Phone Home', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_phone_cell', 'label' => 'E Emergency Contact2 Phone Cell', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_phone_work', 'label' => 'E Emergency Contact2 Phone Work', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_email', 'label' => 'E Emergency Contact2 Email', 'type' => 'string'],
        ['field' => 'e_emergency_contact2_hospital', 'label' => 'E Emergency Contact2 Hospital', 'type' => 'string'],
        ['field' => 'e_insurance_company', 'label' => 'E Insurance Company', 'type' => 'string'],
        ['field' => 'e_insurance_policy_number', 'label' => 'E Insurance Policy Number', 'type' => 'string'],
        ['field' => 'e_signature', 'label' => 'E Signature', 'type' => 'string'],
        ['field' => 'e_signature_date', 'label' => 'E Signature Date', 'type' => 'date'],
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
        'e_signature_date' => 'date',
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
