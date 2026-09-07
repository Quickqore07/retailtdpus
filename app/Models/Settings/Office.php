<?php

namespace App\Models\Settings;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class Office extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'office';

    public const DEFAULT_TIMEZONE = 'America/New_York';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'authorized_radius',
        'timezone',
        'active',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'latitude', 'label' => 'Latitude', 'type' => 'string'],
        ['field' => 'longitude', 'label' => 'Longitude', 'type' => 'string'],
        ['field' => 'authorized_radius', 'label' => 'Authorized Radius', 'type' => 'string'],
        ['field' => 'timezone', 'label' => 'Timezone', 'type' => 'string'],
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
        'latitude' => 'float',
        'longitude' => 'float',
        'authorized_radius' => 'integer',
    ];

    protected $sortable = [
        'name',
        'latitude',
        'longitude',
        'authorized_radius',
        'timezone',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'name',
        'timezone',
    ];

    protected $allowedFilters = [
        'name',
        'latitude',
        'longitude',
        'authorized_radius',
        'timezone',
        'created_at',
        'updated_at',
    ];

    public function resolvedTimezone(): string
    {
        return $this->timezone ?: self::DEFAULT_TIMEZONE;
    }

    public function users()
    {
        return $this->hasMany(User::class);
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
