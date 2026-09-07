<?php
namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Employee;
use App\Models\Settings\Company;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedCompanies;
class OnboardingList extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedCompanies;

    protected $table = 'onboarding_list';
    protected $indexField = 'onboarding_number';

    protected $activityFields = [
        ['field' => 'onboarding_number', 'label' => 'Onboarding #', 'type' => 'string'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'applicant_first_name', 'label' => 'First Name', 'type' => 'string'],
        ['field' => 'applicant_last_name', 'label' => 'Last Name', 'type' => 'string'],
        ['field' => 'applicant_email', 'label' => 'Email', 'type' => 'string'],
        ['field' => 'applicant_contact_number', 'label' => 'Phone', 'type' => 'string'],
        ['field' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
        ['field' => 'doj', 'label' => 'Date of Join', 'type' => 'date'],
        ['field' => 'status', 'label' => 'Status', 'type' => 'string'],
        ['field' => 'final_status', 'label' => 'Final Status', 'type' => 'string'],
        ['field' => 'i9_completed', 'label' => 'I-9 Completed', 'type' => 'boolean'],
        ['field' => 'w4_completed', 'label' => 'W-4 Completed', 'type' => 'boolean'],
        ['field' => 'i9_approved', 'label' => 'I-9 Approved', 'type' => 'boolean'],
        ['field' => 'w4_approved', 'label' => 'W-4 Approved', 'type' => 'boolean'],
        ['field' => 'document_approved', 'label' => 'Document Approved', 'type' => 'boolean'],
        ['field' => 'workbright_status', 'label' => 'WorkBright Status', 'type' => 'string'],
        ['field' => 'everify_status', 'label' => 'E-Verify Status', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $primaryKey = 'id';

    public $timestamps = true; // Assuming `created_at` and `updated_at` are managed automatically

    protected $fillable = [
        'employee_id',
        'onboarding_number',
        'employee_handbook_agreed',
        'application_job_id',
        'job_id',
        'company_id',
        'store_user_id',
        'user_id',
        'nick_name',
        'real_name',
        'applicant_first_name',
        'applicant_last_name',
        'applicant_middle_initial',
        'applicant_address',
        'apt_number',
        'dob',
        'applicant_contact_number',
        'applicant_email',
        'city',
        'state',
        'zip_code',
        'applicant_status',
        'applicant_hire_detail',
        'sent_email_hire_data',
        'applicant_pay_type',
        'applicant_pay_period',
        'status',
        'final_status',
        'created_by',
        'verified_i9',
        'verified_w4',
        'read_date',
        'verified_date',
        'submit_application_date',
        'remark',
        'alternative_procedure',
        'doj',
        'benifits',
        'date',
        'new_change',
        'final_onboarding_hr_status',
        'hr_verify_date',
        'send_onboarding_email_date',
        'new_process_status',
        'zipcode',
        'picture_file',
        'work_permit_issuer',
        'work_permit_document_path',
        'process_id',
        'final_submission_completed',
        'final_submission_date',
        'digital_signature_completed',
        'digital_signature_date',
        'work_bright_employee_id',
        'work_bright_response',
        'i9_with_work_bright',
        'i9_completed',
        'w4_completed',
        'i9_approved',
        'w4_approved',
        'i9_rejected',
        'w4_rejected',
        'i9_completed_datetime',
        'w4_completed_datetime',
        'i9_approved_datetime',
        'w4_approved_datetime',
        'i9_rejected_datetime',
        'w4_rejected_datetime',
        'i9_rejected_reason',
        'w4_rejected_reason',
        'workbright_status',
        'everify_status',
        'everify_status_updated_at',
        'document_approved',
        'document_approved_at',
        'section_2_verification',
    ];
    protected $sortable = [
        'onboarding_number',
        'applicant_first_name',
        'applicant_last_name',
        'applicant_email',
        'company_id',
        'process_id',
        'submit_application_date',
        'created_at',
    ];
    protected $searchable = [
        'onboarding_number',
        'applicant_first_name',
        'applicant_last_name',
        'applicant_email',
        'company_id',
        'process_id',
        'submit_application_date',
        'created_at',
    ];
    protected $allowedFilters = [
        'employee_id',
        'onboarding_number',
        'employee_handbook_agreed',
        'application_job_id',
        'job_id',
        'company_id',
        'store_user_id',
        'user_id',
        'nick_name',
        'real_name',
        'created_at'
    ];
    protected $casts = [
        'read_date' => 'datetime',
        'verified_date' => 'datetime',
        'doj' => 'date',
        'date' => 'datetime',
        'applicant_hire_detail' => 'array',
        'sent_email_hire_data' => 'array',
        'verified_i9' => 'array',
        'verified_w4' => 'array',
        'final_submission_date' => 'datetime',
        'work_bright_response' => 'array',
        'i9_with_work_bright' => 'boolean',
        'everify_status_updated_at' => 'datetime',
        'document_approved' => 'boolean',
        'document_approved_at' => 'datetime',
        'section_2_verification' => 'boolean',
    ];
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->select('id', 'name', 'store_number');
    }

    public function handbook()
    {
        return $this->hasOne(OnboardingHandbook::class, 'onboarding_id');
    }

    public function formI9()
    {
        return $this->hasOne(FormI9::class, 'onboarding_list_id');
    }

    public function formW4()
    {
        return $this->hasOne(FormW4::class, 'onboarding_list_id');
    }

    public function i9W4()
    {
        return $this->hasOne(I9W4::class, 'onboarding_list_id');
    }

    public function benefitEnrollment()
    {
        return $this->hasOne(BenefitEnrollment::class, 'onboarding_list_id');
    }

    public function directDeposit()
    {
        return $this->hasOne(DirectDeposit::class, 'onboarding_list_id');
    }

    public function emergencyContact()
    {
        return $this->hasOne(EmergencyContact::class, 'onboarding_list_id');
    }

    public function finalSubmission()
    {
        return $this->hasOne(FinalSubmission::class, 'onboarding_list_id');
    }

    public function digitalSignature()
    {
        return $this->hasOne(DigitalSignature::class, 'onboarding_list_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
