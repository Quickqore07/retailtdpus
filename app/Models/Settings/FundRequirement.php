<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Support\AuthorizedCompanies;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
use App\Models\User;
class FundRequirement extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'fund_requirements';

    protected $fillable = [
        'label',
        'type',
        'condition_type',
        'condition_value',
        'amount',
        'active',
        'order',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'label', 'label' => 'Label', 'type' => 'string'],
        ['field' => 'type', 'label' => 'Type', 'type' => 'string'],
        ['field' => 'condition_type', 'label' => 'Condition Type', 'type' => 'string'],
        ['field' => 'condition_value', 'label' => 'Condition Value', 'type' => 'string'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
        ['field' => 'order', 'label' => 'Order', 'type' => 'string'],
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
        'active' => 'boolean',
        'order' => 'integer',
    ];

    protected $sortable = [
        'label',
        'type',
        'condition_type',
        'condition_value',
        'amount',
        'order',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'label',
        'type',
        'condition_type',
    ];

    protected $searchable = [
        'label',
        'type',
        'condition_type',
    ];

    protected $allowedFilters = [
        'label',
        'type',
        'condition_type',
        'condition_value',
        'active',
        'created_at',
        'updated_at',
    ];

    public function companies()
    {
        return $this->hasMany(FundRequirementCompany::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }
    public function getTotalAmountAttribute()
    {
        if ($this->type === 'fixed') {
            return $this->amount;
        }
        
        return $this->companies()->sum('amount');
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

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
