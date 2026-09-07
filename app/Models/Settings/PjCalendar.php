<?php

namespace App\Models\Settings;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PjCalendar extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'pj_calendars';

    protected $fillable = [
        'year',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'year', 'label' => 'Year', 'type' => 'string'],
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
        'year' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'year',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'year',
    ];

    protected $allowedFilters = [
        'year',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'year',
    ];

    public function items()
    {
        return $this->hasMany(PjCalendarItem::class)->orderBy('id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
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
