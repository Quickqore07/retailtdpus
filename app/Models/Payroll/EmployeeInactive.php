<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;
use App\Models\Settings\Company;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\AuthorizedCompanies;
use App\Models\Traits\LogsActivity;

class EmployeeInactive extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;
    protected $table = 'employee_inactive';
    protected $fillable = ['employee_id', 'company_id', 'from_date', 'to_date'];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
        ['field' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];
    protected $searchableColumns = [
        'from_date',
        'to_date',
    ];
    protected $sortable = [
        'employee_id',
        'company_id',
        'from_date',
        'to_date',
        'created_at',
        'updated_at',
    ];
    protected $searchable = [
        'employee_id',
        'company_id',
        'from_date',
        'to_date',
    ];
    protected $allowedFilters = [
        'employee_id',
        'company_id',
        'from_date',
        'to_date',
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'name', 'store_number')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }
}