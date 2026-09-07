<?php

namespace App\Models\DataEntry;

use App\Models\Traits\LogsActivity;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Support\AuthorizedCompanies;
class FoodPurchase extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedCompanies;

    protected $table = 'food_purchase';

    protected $fillable = [
        'date',
        'total_amount', 
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $searchableColumns = [
        'date',
        'total_amount',
    ];
    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'date',
        'total_amount',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $searchable = [
        'date',
        'total_amount',
    ];

    protected $allowedFilters = [
        'date',
        'total_amount',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];


    public function items()
    {
        return $this->hasMany(FoodPurchaseItems::class, 'food_purchase_id');
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
