<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Support\AuthorizedCompanies;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
    class Workgroup extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'workgroup';

    protected $fillable = [
        'name',
        'active',
        'only_upload',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
        ['field' => 'only_upload', 'label' => 'Only Upload', 'type' => 'boolean'],
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
        'active' => 'boolean',
        'only_upload' => 'boolean',
    ];
    

    protected $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];
    protected $searchableColumns = [
        'name',
    ];
    protected $searchable = [
        'name',
    ];

    protected $allowedFilters = [
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the companies for the workgroup.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Scope a query to only include active workgroups.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    public function scopeOnlyRegular($query,$field_name = 'only_upload')
    {
        return $query->where($field_name, 0);
    }

    /**
     * Scope a query to only include upload workgroups.
     */
    public function scopeOnlyUpload($query)
    {
        return $query->where('only_upload', true);
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

