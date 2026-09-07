<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
class State extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'state';

    protected $fillable = [
        'name',
        'active',
    ];

    protected $activityFields = [
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
        'created_at',
        'updated_at',
    ];

    /**
     * Get the counties for the state.
     */
    public function counties()
    {
        return $this->hasMany(County::class);
    }

    /**
     * Get the regions for the state.
     */
    public function regions()
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Get the companies for the state.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the minimum wages for the state.
     */
    public function minimumWages()
    {
        return $this->hasMany(MinimumWage::class);
    }

    /**
     * Scope a query to only include active states.
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

