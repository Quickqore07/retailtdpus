<?php

namespace App\Models\AR;

use App\Models\Settings\Company;
use App\Support\AuthorizedCompanies;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PjPayment extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'pj_payments';

    protected $fillable = [
        'company_id',
        'date',
        'total_amount',
        'is_imported',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number'],
        ['field' => 'is_imported', 'label' => 'Is Imported', 'type' => 'boolean'],
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
        'is_imported' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'company.store_number',
        'pj_payments.date',
        'pj_payments.total_amount',
        'pj_payments.created_at',
        'pj_payments.updated_at',
    ];

    protected $searchable = [
        'company_id',
        'date',
        'total_amount',
    ];

    protected $allowedFilters = [
        'company_id',
        'date',
        'total_amount',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }

    public function items()
    {
        return $this->hasMany(PjPaymentItem::class, 'pj_payment_id')->with('bankChildAmount');
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
