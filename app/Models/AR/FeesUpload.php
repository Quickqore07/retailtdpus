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

class FeesUpload extends Model
{
    use HasFactory, Filterable, AuthorizedCompanies, LogsActivity;

    protected $table = 'fees_uploads';

    protected $fillable = [
        'date',
        'company_id',
        'ddd_cash',
        'ez_cater',
        'meal_deal',
        'visa',
        'amex',
        'doordash',
        'ddc_doordash',
        'uber',
        'grubhub',
        'total_amount',
        'is_imported',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'ddd_cash', 'label' => 'Ddd Cash', 'type' => 'string'],
        ['field' => 'ez_cater', 'label' => 'Ez Cater', 'type' => 'string'],
        ['field' => 'meal_deal', 'label' => 'Meal Deal', 'type' => 'string'],
        ['field' => 'visa', 'label' => 'Visa', 'type' => 'string'],
        ['field' => 'amex', 'label' => 'Amex', 'type' => 'string'],
        ['field' => 'doordash', 'label' => 'Doordash', 'type' => 'string'],
        ['field' => 'ddc_doordash', 'label' => 'Ddc Doordash', 'type' => 'string'],
        ['field' => 'uber', 'label' => 'Uber', 'type' => 'string'],
        ['field' => 'grubhub', 'label' => 'Grubhub', 'type' => 'string'],
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
        'ddd_cash',
        'ez_cater',
        'meal_deal',
        'visa',
        'amex',
        'doordash',
        'ddc_doordash',
        'uber',
        'grubhub',
        'total_amount',
    ];

    protected $casts = [
        'ddd_cash' => 'decimal:2',
        'ez_cater' => 'decimal:2',
        'meal_deal' => 'decimal:2',
        'visa' => 'decimal:2',
        'amex' => 'decimal:2',
        'doordash' => 'decimal:2',
        'ddc_doordash' => 'decimal:2',
        'uber' => 'decimal:2',
        'grubhub' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_imported' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'fees_uploads.date',
        'company.store_number',
        'fees_uploads.total_amount',
        'fees_uploads.created_at',
        'fees_uploads.updated_at',
    ];

    protected $searchable = [
        'date',
        'company_id',
        'total_amount',
    ];

    protected $allowedFilters = [
        'date',
        'company_id',
        'ddd_cash',
        'ez_cater',
        'meal_deal',
        'visa',
        'amex',
        'doordash',
        'ddc_doordash',
        'uber',
        'grubhub',
        'total_amount',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number, state_id');
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

            $model->total_amount = $model->ddd_cash + $model->ez_cater + $model->meal_deal + $model->visa + $model->amex + $model->doordash + $model->ddc_doordash + $model->uber + $model->grubhub;
        });

        static::updating(function ($model) {
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }

            $model->total_amount = $model->ddd_cash + $model->ez_cater + $model->meal_deal + $model->visa + $model->amex + $model->doordash + $model->ddc_doordash + $model->uber + $model->grubhub;
        });
    }
}
