<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedWorkgroup;
class Role extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedWorkgroup;
    
    protected $fillable = ['name', 'permissions', 'notification_permissions', 'companies', 'workgroup_id', 'folder_access'];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'notification_permissions', 'label' => 'Notification Permissions', 'type' => 'string'],
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
    
    protected $casts = [
        'permissions' => 'array',
        'notification_permissions' => 'array',
        'folder_access' => 'array',
    ];
    
    protected $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];
    
    protected $searchable = [
        'name',
    ];
    protected $searchableColumns = [
        'name',
    ];

    protected $allowedFilters = [
        'name',
        'created_at',
        'updated_at',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getCompaniesArrayAttribute()
    {
        if (!$this->companies) {
            return [];
        }

        return array_map('intval', explode(',', $this->companies));
    }

    public function folders()
    {
        if (!$this->folder_access) {
            return [];
        }
        return \App\Models\Upload\UploadFolder::whereIn('id', $this->folder_access)->selectRaw('id, name')->get();
    }

}