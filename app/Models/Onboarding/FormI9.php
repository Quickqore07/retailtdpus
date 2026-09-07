<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;

class FormI9 extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'i9_forms';

    protected $fillable = [
        'employee_id',
        'onboarding_list_id',
        // Section 1 - only I-9 specific (name/address/DOB/SSN/email/phone from onboarding_list or employee)
        'other_last_names',
        'citizenship_status',
        'uscis_or_a_number',
        'alien_authorized_exp_date',
        'uscis_a_number',
        'form_i94_admission_number',
        'foreign_passport_number',
        'employee_signature',
        'section1_today_date',
        // Section 2
        'list_a_doc_title_1',
        'list_a_issuing_authority_1',
        'list_a_document_number_1',
        'list_a_expiration_date_1',
        'list_a_doc_title_2',
        'list_a_issuing_authority_2',
        'list_a_document_number_2',
        'list_a_expiration_date_2',
        'list_a_doc_title_3',
        'list_a_issuing_authority_3',
        'list_a_document_number_3',
        'list_a_expiration_date_3',
        'list_b_doc_title',
        'list_b_issuing_authority',
        'list_b_document_number',
        'list_b_expiration_date',
        'list_c_doc_title',
        'list_c_issuing_authority',
        'list_c_document_number',
        'list_c_expiration_date',
        'additional_information',
        'alternative_procedure',
        'first_day_employment',
        'employer_name',
        'employer_signature',
        'employer_today_date',
        'employer_business_name',
        'employer_business_address',
        'list_a_file_path',
        'list_b_file_path',
        'list_c_file_path',
        'additional_document_path',
        'additional_document_label',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'onboarding_list_id', 'label' => 'Onboarding #', 'table' => 'onboarding_list', 'table_field' => 'onboarding_number', 'type' => 'lookup'],
        ['field' => 'other_last_names', 'label' => 'Other Last Names', 'type' => 'string'],
        ['field' => 'citizenship_status', 'label' => 'Citizenship Status', 'type' => 'string'],
        ['field' => 'uscis_or_a_number', 'label' => 'Uscis Or A Number', 'type' => 'string'],
        ['field' => 'alien_authorized_exp_date', 'label' => 'Alien Authorized Exp Date', 'type' => 'date'],
        ['field' => 'uscis_a_number', 'label' => 'Uscis A Number', 'type' => 'string'],
        ['field' => 'form_i94_admission_number', 'label' => 'Form I94 Admission Number', 'type' => 'string'],
        ['field' => 'foreign_passport_number', 'label' => 'Foreign Passport Number', 'type' => 'string'],
        ['field' => 'employee_signature', 'label' => 'Employee Signature', 'type' => 'string'],
        ['field' => 'section1_today_date', 'label' => 'Section1 Today Date', 'type' => 'date'],
        ['field' => 'list_a_doc_title_1', 'label' => 'List A Doc Title 1', 'type' => 'string'],
        ['field' => 'list_a_issuing_authority_1', 'label' => 'List A Issuing Authority 1', 'type' => 'string'],
        ['field' => 'list_a_document_number_1', 'label' => 'List A Document Number 1', 'type' => 'string'],
        ['field' => 'list_a_expiration_date_1', 'label' => 'List A Expiration Date 1', 'type' => 'string'],
        ['field' => 'list_a_doc_title_2', 'label' => 'List A Doc Title 2', 'type' => 'string'],
        ['field' => 'list_a_issuing_authority_2', 'label' => 'List A Issuing Authority 2', 'type' => 'string'],
        ['field' => 'list_a_document_number_2', 'label' => 'List A Document Number 2', 'type' => 'string'],
        ['field' => 'list_a_expiration_date_2', 'label' => 'List A Expiration Date 2', 'type' => 'string'],
        ['field' => 'list_a_doc_title_3', 'label' => 'List A Doc Title 3', 'type' => 'string'],
        ['field' => 'list_a_issuing_authority_3', 'label' => 'List A Issuing Authority 3', 'type' => 'string'],
        ['field' => 'list_a_document_number_3', 'label' => 'List A Document Number 3', 'type' => 'string'],
        ['field' => 'list_a_expiration_date_3', 'label' => 'List A Expiration Date 3', 'type' => 'string'],
        ['field' => 'list_b_doc_title', 'label' => 'List B Doc Title', 'type' => 'string'],
        ['field' => 'list_b_issuing_authority', 'label' => 'List B Issuing Authority', 'type' => 'string'],
        ['field' => 'list_b_document_number', 'label' => 'List B Document Number', 'type' => 'string'],
        ['field' => 'list_b_expiration_date', 'label' => 'List B Expiration Date', 'type' => 'date'],
        ['field' => 'list_c_doc_title', 'label' => 'List C Doc Title', 'type' => 'string'],
        ['field' => 'list_c_issuing_authority', 'label' => 'List C Issuing Authority', 'type' => 'string'],
        ['field' => 'list_c_document_number', 'label' => 'List C Document Number', 'type' => 'string'],
        ['field' => 'list_c_expiration_date', 'label' => 'List C Expiration Date', 'type' => 'date'],
        ['field' => 'additional_information', 'label' => 'Additional Information', 'type' => 'string'],
        ['field' => 'alternative_procedure', 'label' => 'Alternative Procedure', 'type' => 'string'],
        ['field' => 'first_day_employment', 'label' => 'First Day Employment', 'type' => 'string'],
        ['field' => 'employer_name', 'label' => 'Employer Name', 'type' => 'string'],
        ['field' => 'employer_signature', 'label' => 'Employer Signature', 'type' => 'string'],
        ['field' => 'employer_today_date', 'label' => 'Employer Today Date', 'type' => 'date'],
        ['field' => 'employer_business_name', 'label' => 'Employer Business Name', 'type' => 'string'],
        ['field' => 'employer_business_address', 'label' => 'Employer Business Address', 'type' => 'string'],
        ['field' => 'list_a_file_path', 'label' => 'List A File Path', 'type' => 'string'],
        ['field' => 'list_b_file_path', 'label' => 'List B File Path', 'type' => 'string'],
        ['field' => 'list_c_file_path', 'label' => 'List C File Path', 'type' => 'string'],
        ['field' => 'additional_document_path', 'label' => 'Additional Document Path', 'type' => 'string'],
        ['field' => 'additional_document_label', 'label' => 'Additional Document Label', 'type' => 'string'],
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
        'alternative_procedure' => 'boolean',
    ];

    public const CITIZENSHIP_STATUS_US_CITIZEN = 1;
    public const CITIZENSHIP_STATUS_NONCITIZEN_NATIONAL = 2;
    public const CITIZENSHIP_STATUS_LPR = 3;
    public const CITIZENSHIP_STATUS_ALIEN_AUTHORIZED = 4;

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }

    public function preparerTranslators()
    {
        return $this->hasMany(I9PreparerTranslator::class, 'i9_form_id');
    }

    public function reverifications()
    {
        return $this->hasMany(I9Reverification::class, 'i9_form_id');
    }

    /**
     * Section 1 employee data: use onboarding_list when available, else employee.
     * Use when displaying or filling the I-9 form so we don't duplicate stored data.
     */
    public function getSection1EmployeeDataAttribute(): array
    {
        $onboarding = $this->onboardingList;
        $emp = $this->employee;

        return [
            'last_name' => $onboarding?->applicant_last_name ?? $emp?->last_name,
            'first_name' => $onboarding?->applicant_first_name ?? $emp?->first_name,
            'middle_initial' => $onboarding?->applicant_middle_initial ?? ($emp?->middle_name ? substr($emp->middle_name, 0, 1) : null),
            'address' => $onboarding?->applicant_address ?? $emp?->street,
            'apt_number' => $onboarding?->apt_number ?? $emp?->apt_number,
            'city' => $onboarding?->city ?? $emp?->city,
            'state' => $onboarding?->state ?? $emp?->state,
            'zipcode' => $onboarding?->zip_code ?? $onboarding?->zipcode ?? $emp?->zip,
            'date_of_birth' => $onboarding?->dob ?? $emp?->dob,
            'social_security_number' => $emp?->ssn,
            'employee_email' => $onboarding?->applicant_email ?? $emp?->email,
            'employee_telephone' => $onboarding?->applicant_contact_number ?? $emp?->phone,
        ];
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
