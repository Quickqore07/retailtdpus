<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\LogsActivity;

class EmployeeSubRoles extends Model
{
    use HasFactory, Filterable, LogsActivity;
    protected $table = 'employee_sub_role';
    protected $fillable = ['code', 'role_id'];

    protected $activityFields = [
        ['field' => 'code', 'label' => 'Code', 'type' => 'string'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'employee_roles', 'table_field' => 'name', 'type' => 'lookup'],
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
        'code',
        'role_id',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'code',
        'created_at',
        'updated_at',
    ];

    protected $allowedFilters = [
        'code',
        'role_id',
        'created_at',
        'updated_at',
    ];

    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id');
    }
}