<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedWorkgroup;

class EmployeeRoles extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedWorkgroup;
    protected $table = 'employee_roles';
    protected $fillable = ['name', 'code', 'active', 'tipped', 'workgroup_id'];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'code', 'label' => 'Code', 'type' => 'string'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
        ['field' => 'tipped', 'label' => 'Tipped', 'type' => 'string'],
        ['field' => 'workgroup_id', 'label' => 'Workgroup', 'table' => 'workgroup', 'table_field' => 'name', 'type' => 'lookup'],
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
        'name',
        'code',
        'active',
        'tipped',
        'workgroup_id',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'name',
        'code',
    ];

    protected $searchable = [
        'name',
        'code',
        'tipped',
        'created_at',
        'updated_at',
    ];

    protected $allowedFilters = [
        'name',
        'code',
        'active',
        'tipped',
        'created_at',
        'updated_at',
    ];


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


    public function subRoles()
    {
        return $this->hasMany(EmployeeSubRoles::class, 'role_id')->select('id', 'code', 'role_id');
    }
}