<?php

namespace App\Models\DataEntry;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class IdealCost extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'ideal_cost';
    protected $indexField = 'date';

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'total_cost', 'label' => 'Total Cost', 'type' => 'number'],
        ['field' => 'total_mileage', 'label' => 'Total Mileage', 'type' => 'number'],
        ['field' => 'total_delivery', 'label' => 'Total Delivery', 'type' => 'number'],
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
        'date',
        'total_cost',
        'total_mileage',
        'total_delivery',
        'created_by',
        'updated_by',
    ];
    protected $searchableColumns = [
        'date'
    ];
    protected $casts = [
        'total_cost' => 'decimal:2',
        'total_mileage' => 'decimal:2',
        'total_delivery' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'date',
        'total_cost',
        'total_mileage',
        'total_delivery',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $searchable = [
        'date',
        'total_cost',
        'total_mileage',
        'total_delivery',
    ];

    protected $allowedFilters = [
        'date',
        'total_cost',
        'total_mileage',
        'total_delivery',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    public function items()
    {
        return $this->hasMany(IdealCostItem::class, 'ideal_cost_id');
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
