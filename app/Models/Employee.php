<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Payroll\EmployeeRates;
use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Settings\Company;
use App\Support\AuthorizedCompanies;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Payroll\EmployeeInactive;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Traits\LogsActivity;
use App\Models\Settings\Workgroup;
use App\Support\AuthorizedWorkgroup;
class Employee extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity, AuthorizedWorkgroup;
    protected $table = 'employee';
    protected $indexField = 'pos_name';

    protected $activityFields = [
        ['field' => 'hire_date', 'label' => 'Hire Date', 'type' => 'date'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'employee_id', 'label' => 'Employee ID', 'type' => 'string'],
        ['field' => 'pos_name', 'label' => 'POS Name', 'type' => 'string'],
        ['field' => 'ssn', 'label' => 'SSN', 'type' => 'string'],
        ['field' => 'check_name', 'label' => 'Check Name', 'type' => 'string'],
        ['field' => 'phone', 'label' => 'Phone', 'type' => 'string'],
        ['field' => 'email', 'label' => 'Email', 'type' => 'string'],
        ['field' => 'street', 'label' => 'Street', 'type' => 'string'],
        ['field' => 'apt_number', 'label' => 'Apt Number', 'type' => 'string'],
        ['field' => 'city', 'label' => 'City', 'type' => 'string'],
        ['field' => 'zip', 'label' => 'Zip', 'type' => 'string'],
        ['field' => 'state', 'label' => 'State', 'type' => 'string'],
        ['field' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
        ['field' => 'payroll_percent', 'label' => 'Payroll Percent', 'type' => 'number'],
        ['field' => 'check_percent', 'label' => 'Check Percent', 'type' => 'number'],
        ['field' => 'card_percent', 'label' => 'Card Percent', 'type' => 'number'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
        ['field' => 'employee_type', 'label' => 'Employee Type', 'type' => 'string'],
        ['field' => 'emergency_contact_name', 'label' => 'Emergency Contact Name', 'type' => 'string'],
        ['field' => 'emergency_contact_phone', 'label' => 'Emergency Contact Phone', 'type' => 'string'],
        ['field' => 'emergency_contact_relationship', 'label' => 'Emergency Contact Relationship', 'type' => 'string'],
        ['field' => 'termination_date', 'label' => 'Termination Date', 'type' => 'date'],
        ['field' => 'check_payment_type', 'label' => 'Check Payment Type', 'type' => 'number'],
        ['field' => 'check_payment_amount', 'label' => 'Check Payment Amount', 'type' => 'number'],
        ['field' => 'onboarding_status', 'label' => 'Onboarding Status', 'type' => 'string'],
        ['field' => 'first_name', 'label' => 'First Name', 'type' => 'string'],
        ['field' => 'last_name', 'label' => 'Last Name', 'type' => 'string'],
        ['field' => 'middle_name', 'label' => 'Middle Name', 'type' => 'string'],
        ['field' => 'workgroup_id', 'label' => 'Workgroup', 'table' => 'workgroup', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'mail_sent', 'label' => 'Mail Sent', 'type' => 'boolean'],
        ['field' => 'benefit_mail_sent', 'label' => 'Benefit Mail Sent', 'type' => 'string'],
        ['field' => 'benefit_submitted_at', 'label' => 'Benefit Submitted At', 'type' => 'date'],
        ['field' => 'rejected', 'label' => 'Rejected', 'type' => 'boolean'],
        ['field' => 'rejected_at', 'label' => 'Rejected At', 'type' => 'date'],
        ['field' => 'rejected_by', 'label' => 'Rejected By', 'table' => 'users', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'rejection_reason', 'label' => 'Rejection Reason', 'type' => 'string'],
        ['field' => 'i9_doc_skip', 'label' => 'I9 Doc Skip', 'type' => 'boolean'],
        ['field' => 'move_from_pending', 'label' => 'Move From Pending', 'type' => 'string'],
        ['field' => 'benefits_state', 'label' => 'Benefits State', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];
    protected $fillable = ['hire_date', 'company_id', 'employee_id', 'pos_name', 'ssn', 'check_name', 'phone', 'email', 'profile_picture', 'street', 'apt_number', 'city', 'zip', 'state', 'dob', 'payroll_percent', 'check_percent', 'card_percent', 'active', 'employee_type', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',  'termination_date', 'check_payment_type', 'check_payment_amount', 'onboarding_status', 'first_name', 'last_name', 'middle_name', 'workgroup_id', 'mail_sent', 'benefit_mail_sent', 'benefit_submitted_at','created_by', 'updated_by', 'rejected', 'rejected_at', 'rejected_by', 'rejection_reason', 'i9_doc_skip', 'move_from_pending', 'benefits_state'];
    protected $sortable = [
        'hire_date',
        'company_id',
        'pos_name',
        'created_at',
        'updated_at',
        'onboarding_status',
        'benefit_mail_sent',
        'benefit_submitted_at',
    ];

    protected $searchable = [
        'hire_date',
        'employee_id',
        'company_id',
        'pos_name',
        'ssn',
        'phone',
        'email',
        'active',
        'created_at',
        'updated_at',
        'onboarding_status',
        'first_name',
        'last_name',
        'middle_name',
        'workgroup_id',
        'mail_sent',
        'i9_doc_skip',
    ];
    
    protected $allowedFilters = [
        'hire_date',
        'employee_id',
        'company_id',
        'pos_name',
        'ssn',
        'phone',
        'email',
        'active',
        'created_at',
        'updated_at',
        'onboarding_status',
        'first_name',
        'last_name',
        'middle_name',
        'workgroup_id',
        'mail_sent',
        'tnc',
        'i9_doc_skip',
        'benefits_state',
        'benefit_mail_sent',
        'benefit_submitted_at',
    ];

    protected $searchableColumns = [
        'employee_id',
        'pos_name',
        'aliases.alias_employee_id',
        'aliases.alias_name',
        'ssn',
        'phone',
        'email',
        'benefits_state',
    ];

    protected $appends = ['profile_picture_url'];
    
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (empty($this->profile_picture)) {
            return null;
        }
    
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
    
        $adapter = Storage::disk($disk);
        /** @var \Illuminate\Contracts\Filesystem\FilesystemAdapter $adapter */
        if ($disk === 's3') {
            return $adapter->temporaryUrl(
                $this->profile_picture,
                now()->addMinutes(30)
            );
        }
    
        return $adapter->url($this->profile_picture);
    }
    public function employeeRates()
    {
        $authorizedCompanies = authorizedCompanies();
        // Debug: Log when this relationship is accessed to track intermittent behavior
        // \Log::debug('employeeRates loaded', ['companies' => $authorizedCompanies, 'has_auth' => \Auth::check()]);
        return $this->hasMany(EmployeeRates::class, 'employee_id')->whereIn('company_id', $authorizedCompanies)->select('id', 'employee_id', 'role_id', 'rate', 'payroll_hours', 'payroll_hours_type', 'pay_type', 'slab_first_hours', 'slab_rest_rate', 'payroll_rate', 'ten99_rate', 'check_payment_type', 'check_payment_amount', 'rate_type','effective_date','company_id', 'till_date', 'payroll_type')->with('role','employeeRatesRequest.company','company')->orderBy('effective_date', 'desc');
    }

    public function employeeRatesUnrestricted()
    {
        return $this->hasMany(EmployeeRates::class, 'employee_id')
            ->select('id', 'employee_id', 'role_id', 'rate', 'payroll_hours', 'payroll_hours_type', 'pay_type', 'slab_first_hours', 'slab_rest_rate', 'payroll_rate', 'ten99_rate', 'check_payment_type', 'check_payment_amount', 'rate_type','effective_date','company_id', 'till_date', 'payroll_type')
            ->orderBy('effective_date', 'desc');
    }
    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'name','store_number')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }
    public function workgroup()
    {
        return $this->belongsTo(Workgroup::class)->select('id', 'name');
    }
    public function employeeRatesUnrestrictedRequests()
    {
        return $this->hasMany(EmployeeRateRequest::class, 'employee_id','id')
                    ->whereIn('status', ['pending', 'rejected']) ->where(function ($q) {
                        $q->whereNull('employee_rate_id')
                        ->orWhere('employee_rate_id', 0);
                    });
    }
    public function employeeRatesRequests()
    {
        $authorizedCompanies = authorizedCompanies();
        return $this->hasMany(EmployeeRateRequest::class, 'employee_id','id')
                    ->whereIn('status', ['pending', 'rejected']) ->where(function ($q) {
                        $q->whereNull('employee_rate_id')
                        ->orWhere('employee_rate_id', 0);
                    })->whereIn('company_id', $authorizedCompanies)->with('role','company');
    }
    public function employeeInactive()
    {
        $authorizedCompanies = authorizedCompanies();
        return $this->hasMany(EmployeeInactive::class, 'employee_id')->whereIn('company_id', $authorizedCompanies)->with('company');
    }

    public function employeeDocuments()
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id')->select('id', 'employee_id', 'document_type', 'document_name', 'document_path');
    }

    public function onboardingList()
    {
        return $this->hasOne(\App\Models\Onboarding\OnboardingList::class, 'employee_id');
    }
    public function formI9()
    {
        return $this->hasOne(\App\Models\Onboarding\FormI9::class, 'employee_id');
    }

    public function employeeConfirmation()
    {
        return $this->hasOne(\App\Models\Onboarding\EmployeeConfirmation::class, 'employee_id');
    }

    public function benefitEnrollment()
    {
        return $this->hasOne(\App\Models\Onboarding\BenefitEnrollment::class, 'employee_id');
    }

    public function aliases()
    {
        return $this->hasMany(EmployeeAlias::class);
    }

    public function aliasIds(): array
    {
        return $this->aliases
            ->pluck('alias_employee_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->values()
            ->all();
    }

    public function aliasNames(): array
    {
        return $this->aliases
            ->pluck('alias_name')
            ->filter(fn ($name) => $name !== null && $name !== '')
            ->values()
            ->all();
    }

    public function allMatchIds(): array
    {
        return collect([$this->employee_id])
            ->merge($this->aliasIds())
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function allMatchNames(): array
    {
        return collect([$this->pos_name])
            ->merge($this->aliasNames())
            ->filter(fn ($name) => $name !== null && $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function syncAliases(?array $aliases): void
    {
        $this->aliases()->delete();
        $rows = collect($aliases ?? [])
            ->map(fn ($a) => [
                'alias_employee_id' => trim((string) ($a['alias_employee_id'] ?? '')) ?: null,
                'alias_name' => trim((string) ($a['alias_name'] ?? '')) ?: null,
            ])
            ->filter(fn ($a) => $a['alias_employee_id'] || $a['alias_name'])
            ->values();
        foreach ($rows as $row) {
            $this->aliases()->create($row);
        }
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