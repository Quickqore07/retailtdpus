<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Support\AuthorizedCompanies;
use Illuminate\Support\Facades\Auth;    
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedWorkgroup;


class Company extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity, AuthorizedWorkgroup;

    protected $table = 'company';
    protected $indexField = 'name';

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'store_number', 'label' => 'Store Number', 'type' => 'string'],
        ['field' => 'workgroup_id', 'label' => 'Workgroup', 'table' => 'workgroup', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'state_id', 'label' => 'State', 'table' => 'state', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'region_id', 'label' => 'Region', 'table' => 'region', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'county_id', 'label' => 'County', 'table' => 'county', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'area_id', 'label' => 'Area', 'table' => 'area', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'address', 'label' => 'Address', 'type' => 'string'],
        ['field' => 'email', 'label' => 'Email', 'type' => 'string'],
        ['field' => 'contact_person', 'label' => 'Contact Person', 'type' => 'string'],
        ['field' => 'contact_number', 'label' => 'Contact Number', 'type' => 'string'],
        ['field' => 'website', 'label' => 'Website', 'type' => 'string'],
        ['field' => 'employer_identification_number', 'label' => 'Employer Identification Number', 'type' => 'string'],
        ['field' => 'payroll_start_date', 'label' => 'Payroll Start Date', 'type' => 'date'],
        ['field' => 'payroll_frequency', 'label' => 'Payroll Frequency', 'type' => 'number'],
        ['field' => 'trash_frequency', 'label' => 'Trash Frequency', 'type' => 'string'],
        ['field' => 'tax', 'label' => 'Tax', 'type' => 'string'],
        ['field' => 'st_number', 'label' => 'St Number', 'type' => 'string'],
        ['field' => 'pin', 'label' => 'Pin', 'type' => 'string'],
        ['field' => 'payroll_percentage', 'label' => 'Payroll Percentage', 'type' => 'number'],
        ['field' => 'sales_tax_percentage', 'label' => 'Sales Tax Percentage', 'type' => 'number'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
        ['field' => 'payroll_id', 'label' => 'Payroll Id', 'type' => 'number'],
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

    protected $fillable = [
        'name',
        'store_number',
        'password',
        'workgroup_id',
        'state_id',
        'region_id',
        'county_id',
        'area_id',
        'address',
        'email',
        'contact_person',
        'contact_number',
        'website',
        'employer_identification_number',
        'payroll_start_date',
        'payroll_frequency',
        'trash_frequency',
        'tax',
        'st_number',
        'pin',
        'payroll_percentage',
        'sales_tax_percentage',
        'active',
        'payroll_id',
        'only_upload',
    ];

    protected $casts = [
        'active' => 'boolean',
        'payroll_start_date' => 'date',
        'payroll_percentage' => 'integer',
        'sales_tax_percentage' => 'integer',
        'payroll_id' => 'string',
        'only_upload' => 'boolean',
    ];

    // protected $hidden = [
    //     'password',
    // ];

    protected $sortable = [
        'name',
        'store_number',
        'workgroup_id',
        'state_id',
        'region_id',
        'county_id',
        'area_id',
        'created_at',
        'updated_at',
    ];
    protected $searchableColumns = [
        'name',
        'store_number',
        'email',
        'contact_person',
    ];
    protected $searchable = [
        'name',
        'store_number',
        'email',
        'contact_person',
    ];

    protected $allowedFilters = [
        'name',
        'store_number',
        'workgroup_id',
        'state_id',
        'region_id',
        'county_id',
        'area_id',
        'email',
        'contact_person',
        'payroll_frequency',
        'trash_frequency',
        'tax',
        'created_at',
        'updated_at',
    ];
    /**
     * Get the workgroup that owns the company.
     */
    public function workgroup()
    {
        return $this->belongsTo(Workgroup::class)->select('id', 'name');
    }

    /**
     * Get the state that owns the company.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the region that owns the company.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the county that owns the company.
     */
    public function county()
    {
        return $this->belongsTo(County::class);
    }

    /**
     * Get the area that owns the company.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include upload companies.
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

