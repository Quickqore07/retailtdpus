<?php

namespace App\Models\Onboarding;

use App\Models\Employee;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EmployeeConfirmation extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'employee_confirmations';

    public const STATUS_UNVERIFIED = 'unverified';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_CURRENTLY_WORKING = 'currently_working';
    public const STATUS_LEFT = 'left';
    public const HR_STATUS_PENDING = 'pending';
    public const HR_STATUS_DOCUMENT_UPLOADED = 'document_uploaded';
    public const HR_STATUS_REVIEWED = 'reviewed';
    public const HR_STATUS_AUTHORISED = 'authorised';
    public const HR_STATUS_REJECTED = 'rejected';
    public const HR_STATUS_TNC = 'tnc';
    public const HR_STATUS_INCORRECT_I9 = 'incorrect_i9';
    public const TAB_PENDING_I9_UPLOAD = 'pending_i9_upload';
    public const TAB_INCORRECT_I9 = 'incorrect_i9';

    protected $fillable = [
        'employee_id',
        'status',
        'i9_choice',
        'uscis_number',
        'work_authorization_exp_date',
        'list_a_doc_type',
        'list_a_issuing_authority',
        'list_a_document_number',
        'list_a_expiration_date',
        'list_b_doc_type',
        'list_b_issuing_authority',
        'list_b_document_number',
        'list_b_expiration_date',
        'list_c_doc_type',
        'list_c_issuing_authority',
        'list_c_document_number',
        'list_c_expiration_date',
        'list_a_doc_id',
        'list_b_doc_id',
        'list_c_doc_id',
        'reviewed_by',
        'review_notes',
        'authorization_doc_id',
        'tnc_doc_type',
        'tnc_document_id',
        'authorized_by',
        'approved_at',
        'hr_status',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'status', 'label' => 'Status', 'type' => 'string'],
        ['field' => 'i9_choice', 'label' => 'I9 Choice', 'type' => 'string'],
        ['field' => 'uscis_number', 'label' => 'Uscis Number', 'type' => 'string'],
        ['field' => 'work_authorization_exp_date', 'label' => 'Work Authorization Exp Date', 'type' => 'date'],
        ['field' => 'list_a_doc_type', 'label' => 'List A Doc Type', 'type' => 'string'],
        ['field' => 'list_a_issuing_authority', 'label' => 'List A Issuing Authority', 'type' => 'string'],
        ['field' => 'list_a_document_number', 'label' => 'List A Document Number', 'type' => 'string'],
        ['field' => 'list_a_expiration_date', 'label' => 'List A Expiration Date', 'type' => 'date'],
        ['field' => 'list_b_doc_type', 'label' => 'List B Doc Type', 'type' => 'string'],
        ['field' => 'list_b_issuing_authority', 'label' => 'List B Issuing Authority', 'type' => 'string'],
        ['field' => 'list_b_document_number', 'label' => 'List B Document Number', 'type' => 'string'],
        ['field' => 'list_b_expiration_date', 'label' => 'List B Expiration Date', 'type' => 'date'],
        ['field' => 'list_c_doc_type', 'label' => 'List C Doc Type', 'type' => 'string'],
        ['field' => 'list_c_issuing_authority', 'label' => 'List C Issuing Authority', 'type' => 'string'],
        ['field' => 'list_c_document_number', 'label' => 'List C Document Number', 'type' => 'string'],
        ['field' => 'list_c_expiration_date', 'label' => 'List C Expiration Date', 'type' => 'date'],
        ['field' => 'list_a_doc_id', 'label' => 'List A Doc Id', 'type' => 'string'],
        ['field' => 'list_b_doc_id', 'label' => 'List B Doc Id', 'type' => 'string'],
        ['field' => 'list_c_doc_id', 'label' => 'List C Doc Id', 'type' => 'string'],
        ['field' => 'reviewed_by', 'label' => 'Reviewed By', 'type' => 'string'],
        ['field' => 'review_notes', 'label' => 'Review Notes', 'type' => 'string'],
        ['field' => 'authorization_doc_id', 'label' => 'Authorization Doc Id', 'type' => 'string'],
        ['field' => 'tnc_doc_type', 'label' => 'Tnc Doc Type', 'type' => 'string'],
        ['field' => 'tnc_document_id', 'label' => 'Tnc Document Id', 'type' => 'string'],
        ['field' => 'authorized_by', 'label' => 'Authorized By', 'type' => 'string'],
        ['field' => 'approved_at', 'label' => 'Approved At', 'type' => 'date'],
        ['field' => 'hr_status', 'label' => 'Hr Status', 'type' => 'string'],
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
        'work_authorization_exp_date' => 'date',
        'list_a_expiration_date' => 'date',
        'list_b_expiration_date' => 'date',
        'list_c_expiration_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $sortable = [
        'employee_id',
        'status',
        'i9_choice',
        'uscis_number',
        'work_authorization_exp_date',
        'reviewed_by',
        'authorized_by',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'status',
        'i9_choice',
        'uscis_number',
        'list_a_doc_type',
        'list_b_doc_type',
        'list_c_doc_type',
        'review_notes',
        'hr_status',
    ];

    protected $allowedFilters = [
        'employee_id',
        'status',
        'i9_choice',
        'uscis_number',
        'work_authorization_exp_date',
        'reviewed_by',
        'authorized_by',
        'approved_at',
        'created_at',
        'updated_at',
        'hr_status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function listADocument()
    {
        return $this->belongsTo(EmployeeDocument::class, 'list_a_doc_id');
    }

    public function listBDocument()
    {
        return $this->belongsTo(EmployeeDocument::class, 'list_b_doc_id');
    }

    public function listCDocument()
    {
        return $this->belongsTo(EmployeeDocument::class, 'list_c_doc_id');
    }

    public function authorizationDocument()
    {
        return $this->belongsTo(EmployeeDocument::class, 'authorization_doc_id');
    }

    public function tncDocument()
    {
        return $this->belongsTo(EmployeeDocument::class, 'tnc_document_id');
    }

    public function getSection1EmployeeDataAttribute(): array
    {
        $employee = $this->employee;

        if (!$employee) {
            return [];
        }

        return [
            'last_name' => $employee->last_name,
            'first_name' => $employee->first_name,
            'middle_initial' => $employee->middle_name
                ? substr($employee->middle_name, 0, 1)
                : null,
            'address' => $employee->street,
            'apt_number' => $employee->apt_number,
            'city' => $employee->city,
            'state' => $employee->state,
            'zipcode' => $employee->zip,
            'date_of_birth' => $employee->dob,
            'social_security_number' => $employee->ssn,
            'employee_email' => $employee->email,
            'employee_telephone' => $employee->phone,
        ];
    }

    public function getPdfCitizenshipStatusAttribute(): ?int
    {
        return match ($this->i9_choice) {
            'citizen' => 1,
            'national' => 2,
            'lpr' => 3,
            'alien' => 4,
            default => null,
        };
    }

    public function getPdfUscisOrANumberAttribute(): ?string
    {
        return $this->i9_choice === 'lpr' ? $this->uscis_number : null;
    }

    public function getPdfUscisANumberAttribute(): ?string
    {
        if ($this->i9_choice !== 'alien' || !$this->uscis_number) {
            return null;
        }

        $value = $this->uscis_number;

        if (str_starts_with($value, 'I94:') || str_starts_with($value, 'FP:')) {
            return null;
        }

        return $value;
    }

    public function getPdfFormI94AdmissionNumberAttribute(): ?string
    {
        if ($this->i9_choice !== 'alien' || !$this->uscis_number) {
            return null;
        }

        return str_starts_with($this->uscis_number, 'I94:')
            ? substr($this->uscis_number, 4)
            : null;
    }

    public function getPdfForeignPassportNumberAttribute(): ?string
    {
        if ($this->i9_choice !== 'alien' || !$this->uscis_number) {
            return null;
        }

        if (!str_starts_with($this->uscis_number, 'FP:')) {
            return null;
        }

        $payload = substr($this->uscis_number, 3);
        [$passport, $country] = array_pad(explode('|', $payload, 2), 2, null);

        return trim(($passport ?? '') . ($country ? ' - ' . $country : ''));
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
            $model->updated_by = Auth::id();
        });
    }
}
