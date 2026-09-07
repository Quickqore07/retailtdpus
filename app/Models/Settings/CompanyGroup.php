<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class CompanyGroup extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'company_groups';

    protected $fillable = [
        'name',
        'description',
        'active',
        'companies',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'description', 'label' => 'Description', 'type' => 'string'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
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
        'companies' => 'array',
    ];

    protected $sortable = [
        'name',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'name',
        'description',
    ];

    protected $searchable = [
        'name',
        'description',
        'companies',
    ];

    protected $allowedFilters = [
        'name',
        'description',
        'active',
        'created_at',
        'updated_at',
    ];

    

    /**
     * Scope a query to only include active company groups.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the count of companies in this group.
     */

    public function getCompanyArrayAttribute()
    {
        if (!$this->companies) {
            return [];
        }
        return array_map('intval', $this->companies);
    }

    public function companiesDetails()
    {
        return Company::whereIn('id', $this->company_array)->with('workgroup')->selectRaw('id, concat(store_number, " - ", name) as name, store_number, workgroup_id')->get();
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