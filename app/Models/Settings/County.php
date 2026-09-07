<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class County extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'county';

    protected $fillable = [
        'state_id',
        'name',
        'active',
    ];

    protected $activityFields = [
        ['field' => 'state_id', 'label' => 'State', 'table' => 'state', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
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
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'name',
    ];

    protected $allowedFilters = [
        'name',
        'state_id',
        'created_at',
        'updated_at',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the companies for the county.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the minimum wages for the county.
     */
    public function minimumWages()
    {
        return $this->hasMany(MinimumWage::class);
    }

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
