<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class Area extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'area';

    protected $fillable = [
        'name',
        'region_id',
        'active',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'region_id', 'label' => 'Region', 'table' => 'region', 'table_field' => 'name', 'type' => 'lookup'],
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
    ];

    protected $sortable = [
        'name',
        'region_id',
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
        'region_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the region that owns the area.
     */
    public function region()
    {
        return $this->belongsTo(Region::class)->select('id', 'name', 'state_id');
    }

    /**
     * Get the companies for the area.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Scope a query to only include active areas.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
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

