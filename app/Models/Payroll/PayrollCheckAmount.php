<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedCompanies;
use App\Models\User;
class PayrollCheckAmount extends Model
{
    use HasFactory, LogsActivity, AuthorizedCompanies;
    protected $table = 'payroll_check_amounts';
    protected $fillable = ['employee_id', 'company_id', 'role_id', 'eow', 'amount', 'payroll_amount','instant_amount', 'review_by', 'reviewed_at'];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'eow', 'label' => 'Eow', 'type' => 'string'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'payroll_amount', 'label' => 'Payroll Amount', 'type' => 'number'],
        ['field' => 'instant_amount', 'label' => 'Instant Amount', 'type' => 'number'],
        ['field' => 'review_by', 'label' => 'Review By', 'type' => 'string'],
        ['field' => 'reviewed_at', 'label' => 'Reviewed At', 'type' => 'date'],
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
        'amount' => 'decimal:2',
        'payroll_amount' => 'decimal:2',
        'instant_amount' => 'decimal:2',
        'review_by' => 'integer',
        'reviewed_at' => 'datetime',
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class)->select('id', 'employee_id', 'pos_name','company_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'review_by', 'id')->select('id', 'name');
    }
}