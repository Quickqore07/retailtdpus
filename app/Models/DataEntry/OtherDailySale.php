<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Company;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\AuthorizedCompanies;
use App\Support\AuthorizedWorkgroup;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OtherDailySale extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedCompanies, AuthorizedWorkgroup;

    protected $table = 'other_daily_sales';
    protected $indexField = 'date';

    protected $fillable = [
        'date',
        'company_id',
        'sales',
        'tax',
        'other',
        'total',
        'round_off',
        'cash',
        'credit_card',
        'account',
        'check',
        'coupon',
        'other_payment',
        'total_payment',
        'cash_due',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'sales' => 'decimal:2',
        'tax' => 'decimal:2',
        'other' => 'decimal:2',
        'total' => 'decimal:2',
        'round_off' => 'decimal:2',
        'cash' => 'decimal:2',
        'credit_card' => 'decimal:2',
        'account' => 'decimal:2',
        'check' => 'decimal:2',
        'coupon' => 'decimal:2',
        'other_payment' => 'decimal:2',
        'total_payment' => 'decimal:2',
        'cash_due' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'date',
        'company_id',
        'sales',
        'tax',
        'total',
        'cash',
        'total_payment',
        'cash_due',
        'created_at',
    ];

    protected $searchable = [
        'date',
        'sales',
        'tax',
        'total',
        'cash',
        'total_payment',
        'cash_due',
    ];

    protected $allowedFilters = [
        'date',
        'company_id',
        'sales',
        'tax',
        'total',
        'cash',
        'total_payment',
        'cash_due',
        'created_at',
        'updated_at',
    ];

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'sales', 'label' => 'Sales', 'type' => 'number'],
        ['field' => 'tax', 'label' => 'Tax', 'type' => 'number'],
        ['field' => 'other', 'label' => 'Other', 'type' => 'number'],
        ['field' => 'total', 'label' => 'Total', 'type' => 'number'],
        ['field' => 'round_off', 'label' => 'Round Off', 'type' => 'number'],
        ['field' => 'cash', 'label' => 'Cash', 'type' => 'number'],
        ['field' => 'credit_card', 'label' => 'Credit Card', 'type' => 'number'],
        ['field' => 'account', 'label' => 'Account', 'type' => 'number'],
        ['field' => 'check', 'label' => 'Check', 'type' => 'number'],
        ['field' => 'coupon', 'label' => 'Coupon', 'type' => 'number'],
        ['field' => 'other_payment', 'label' => 'Other Payment', 'type' => 'number'],
        ['field' => 'total_payment', 'label' => 'Total Payment', 'type' => 'number'],
        ['field' => 'cash_due', 'label' => 'Cash Due', 'type' => 'number'],
    ];

    public function cashReconciliations()
    {
        return $this->hasMany(OtherDailySaleCashReconciliation::class, 'other_daily_sale_id');
    }

    public function bankDeposits()
    {
        return $this->hasMany(OtherDailySaleBankDeposit::class, 'other_daily_sale_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')
            ->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number');
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
            $model->updated_by = Auth::id();
        });
    }
}
