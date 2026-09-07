<?php

namespace App\Models\DataEntry;

use App\Models\User;
use App\Models\Traits\LogsActivity;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BankDeposit extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'bank_deposits';

    protected $fillable = [
        'daily_sale_id',
        'date',
        'amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'daily_sale_id', 'label' => 'Daily Sale Id', 'type' => 'string'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'notes', 'label' => 'Notes', 'type' => 'string'],
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
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function dailySale()
    {
        return $this->belongsTo(DailySale::class, 'daily_sale_id');
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