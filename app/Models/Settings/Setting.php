<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'value_type',
        'active',
    ];

    protected $activityFields = [
        ['field' => 'key', 'label' => 'Key', 'type' => 'string'],
        ['field' => 'value', 'label' => 'Value', 'type' => 'string'],
        ['field' => 'value_type', 'label' => 'Value Type', 'type' => 'string'],
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
        'value_type' => 'string',
    ];

    protected $sortable = [
        'key',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'key',
        'description',
    ];

    protected $allowedFilters = [
        'name',
        'description',
        'active',
        'created_at',
        'updated_at',
    ];

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
