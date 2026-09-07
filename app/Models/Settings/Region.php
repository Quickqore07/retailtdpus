<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
class Region extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'region';

    protected $fillable = [
        'name',
        'state_id',
        'active',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'state_id', 'label' => 'State', 'table' => 'state', 'table_field' => 'name', 'type' => 'lookup'],
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
        'state_id',
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

    /**
     * Get the state that owns the region.
     */
    public function state()
    {
        return $this->belongsTo(State::class)->select('id', 'name');
    }

    /**
     * Get the areas for the region.
     */
    public function areas()
    {
        return $this->hasMany(Area::class)->select('id', 'name');
    }

    /**
     * Get the companies for the region.
     */
    public function companies()
    {
        return $this->hasMany(Company::class)->select('id', 'name');
    }

    /**
     * Scope a query to only include active regions.
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

