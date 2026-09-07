<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedCompanies;
use App\Support\AuthorizedWorkgroup;
class CheckMaster extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedCompanies, AuthorizedWorkgroup;

    protected $table = 'check_master';

    protected $fillable = [
        'ledger_id',
        'company_id',
        'from_company_id',
        'employee_id',
        'check_number',
        'check_date',
        'payroll_eow',
        'check_amount',
        'check_type',
        'check_memo',
        'role_id',
        'created_by',
        'updated_by',

        'am_reviewed',
        'am_reviewed_by',
        'am_reviewed_at',

        'hr_reviewed',
        'hr_reviewed_by',
        'hr_reviewed_at',

        'admin_reviewed',
        'admin_reviewed_by',
        'admin_reviewed_at',

        'uploaded',
        'uploaded_at',
        'uploaded_id',
    ];

    protected $activityFields = [
        ['field' => 'ledger_id', 'label' => 'Ledger', 'table' => 'ledgers', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'from_company_id', 'label' => 'From Company Id', 'type' => 'string'],
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'check_number', 'label' => 'Check Number', 'type' => 'string'],
        ['field' => 'check_date', 'label' => 'Check Date', 'type' => 'date'],
        ['field' => 'payroll_eow', 'label' => 'Payroll Eow', 'type' => 'number'],
        ['field' => 'check_amount', 'label' => 'Check Amount', 'type' => 'number'],
        ['field' => 'check_type', 'label' => 'Check Type', 'type' => 'string'],
        ['field' => 'check_memo', 'label' => 'Check Memo', 'type' => 'string'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'am_reviewed', 'label' => 'Am Reviewed', 'type' => 'string'],
        ['field' => 'am_reviewed_by', 'label' => 'Am Reviewed By', 'type' => 'string'],
        ['field' => 'am_reviewed_at', 'label' => 'Am Reviewed At', 'type' => 'date'],
        ['field' => 'hr_reviewed', 'label' => 'Hr Reviewed', 'type' => 'string'],
        ['field' => 'hr_reviewed_by', 'label' => 'Hr Reviewed By', 'type' => 'string'],
        ['field' => 'hr_reviewed_at', 'label' => 'Hr Reviewed At', 'type' => 'date'],
        ['field' => 'admin_reviewed', 'label' => 'Admin Reviewed', 'type' => 'string'],
        ['field' => 'admin_reviewed_by', 'label' => 'Admin Reviewed By', 'type' => 'string'],
        ['field' => 'admin_reviewed_at', 'label' => 'Admin Reviewed At', 'type' => 'date'],
        ['field' => 'uploaded', 'label' => 'Uploaded', 'type' => 'string'],
        ['field' => 'uploaded_at', 'label' => 'Uploaded At', 'type' => 'date'],
        ['field' => 'uploaded_id', 'label' => 'Uploaded Id', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $sortable = [
        'ledger_id',
        'company_id',
        'from_company_id',
        'employee_id',
        'check_number',
        'check_date',
        'payroll_eow',
        'check_amount',
        'check_type',
        'check_memo',
        'created_by',
        'updated_by',
        'created_at',
        'am_reviewed',
        'am_reviewed_by',
        'hr_reviewed',
        'hr_reviewed_by',
        'am_reviewed_at',
        'hr_reviewed_at',
        'admin_reviewed',
        'admin_reviewed_by',
        'admin_reviewed_at',
        'uploaded',
        'uploaded_at',
        'uploaded_id',
    ];

    protected $searchable = [
        'ledger_id',
        'company_id',
        'employee_id',
        'check_number',
        'check_date',
        'payroll_eow',
        'check_amount',
        'check_type',
        'check_memo',
        'created_by',
        'updated_by',
        'created_at',
    ];

    protected $allowedFilters = [
        'ledger_id',
        'company_id',
        'employee_id',
        'check_number',
        'check_date',
        'payroll_eow',
        'check_amount',
        'check_type',
        'check_memo',
        'created_by',
        'updated_by',
        'created_at',
        'am_reviewed',
        'am_reviewed_by',
        'am_reviewed_at',
        'hr_reviewed',
        'hr_reviewed_by',
        'hr_reviewed_at',
        'admin_reviewed',
        'admin_reviewed_by',
        'admin_reviewed_at',
        'uploaded',
        'uploaded_at',
        'uploaded_id',
    ];

    protected function getLoggableAttributesList(): ?array
    {
        return [
            'ledger_id',
            'employee_id',
            'check_number',
            'check_date',
            'payroll_eow',
            'check_amount',
        ];
    }
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    public function fromCompany()
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function amReviewer()
    {
        return $this->belongsTo(User::class, 'am_reviewed_by')->select('id', 'name');
    }

    public function hrReviewer()
    {
        return $this->belongsTo(User::class, 'hr_reviewed_by')->select('id', 'name');
    }

    public function adminReviewer()
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by')->select('id', 'name');
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
