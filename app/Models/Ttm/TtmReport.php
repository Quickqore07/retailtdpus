<?php

namespace App\Models\Ttm;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TtmReport extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'ttm_reports';

    protected $fillable = [
        'store_number',
        'year',
        'month',
        'label',
        'amount',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'store_number', 'label' => 'Store Number', 'type' => 'string'],
        ['field' => 'year', 'label' => 'Year', 'type' => 'string'],
        ['field' => 'month', 'label' => 'Month', 'type' => 'string'],
        ['field' => 'label', 'label' => 'Label', 'type' => 'string'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
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
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $defaultSortColumn = 'created_at';

    protected $defaultSortDirection = 'desc';

    protected $sortable = [
        'year',
        'month',
        'row_count',
        'store_count',
        'created_at',
    ];

    protected $allowedFilters = [
        'year',
        'month',
    ];

    protected $searchableColumns = [
        'year',
        'month',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
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
