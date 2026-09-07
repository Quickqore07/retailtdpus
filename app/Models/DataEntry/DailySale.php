<?php

namespace App\Models\DataEntry;

use App\Models\DataEntry\BankDeposit;
use App\Models\DataEntry\DailySaleOtherPayment;
use App\Models\Settings\Company;
use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Support\AuthorizedCompanies;
use App\Support\AuthorizedWorkgroup;

class DailySale extends Model
{
    use HasFactory, Filterable, LogsActivity, AuthorizedCompanies, AuthorizedWorkgroup;

    protected $table = 'daily_sales';
    protected $indexField = 'date';

    protected $activityFields = [
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'net_sales', 'label' => 'Net Sales', 'type' => 'string'],
        ['field' => 'beverage_tax', 'label' => 'Beverage Tax', 'type' => 'string'],
        ['field' => 'food_tax', 'label' => 'Food Tax', 'type' => 'string'],
        ['field' => 'total_sales', 'label' => 'Total Sales', 'type' => 'number'],
        ['field' => 'cash_received', 'label' => 'Cash Received', 'type' => 'string'],
        ['field' => 'partial_void', 'label' => 'Partial Void', 'type' => 'string'],
        ['field' => 'total_cash', 'label' => 'Total Cash', 'type' => 'number'],
        ['field' => 'tips', 'label' => 'Tips', 'type' => 'number'],
        ['field' => 'mileage', 'label' => 'Mileage', 'type' => 'number'],
        ['field' => 'total_tips_mileage', 'label' => 'Total Tips Mileage', 'type' => 'number'],
        ['field' => 'dd_tips', 'label' => 'Dd Tips', 'type' => 'number'],
        ['field' => 'e_tips', 'label' => 'E Tips', 'type' => 'number'],
        ['field' => 'e_tips_payroll', 'label' => 'E Tips Payroll', 'type' => 'number'],
        ['field' => 'total_e_and_dd_tips', 'label' => 'Total E And Dd Tips', 'type' => 'number'],
        ['field' => 'cash_payment', 'label' => 'Cash Payment', 'type' => 'number'],
        ['field' => 'other_payments_total', 'label' => 'Other Payments Total', 'type' => 'number'],
        ['field' => 'total_cash_payment', 'label' => 'Total Cash Payment', 'type' => 'number'],
        ['field' => 'net_cash_due', 'label' => 'Net Cash Due', 'type' => 'string'],
        ['field' => 'cash_bag', 'label' => 'Cash Bag', 'type' => 'string'],
        ['field' => 'short_over', 'label' => 'Short Over', 'type' => 'string'],
        ['field' => 'settled', 'label' => 'Settled', 'type' => 'string'],
        ['field' => 'bank_child_amount_id', 'label' => 'Bank Child Amount Id', 'type' => 'number'],
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
        'company_id',
        'net_sales',
        'beverage_tax',
        'food_tax',
        'total_sales',
        'cash_received',
        'partial_void',
        'total_cash',
        'tips',
        'mileage',
        'total_tips_mileage',
        'dd_tips',
        'e_tips',
        'e_tips_payroll',
        'total_e_and_dd_tips',
        'cash_payment',
        'other_payments_total',
        'total_cash_payment',
        'net_cash_due',
        'cash_bag',
        'short_over',
        'created_by',
        'updated_by',
        'settled',
        'bank_child_amount_id',
    ];
    protected $searchableColumns = [
        'date',
        'total_sales',
        'net_cash_due',
        'cash_bag',
        'short_over',
        'partial_void',
    ];
    protected $casts = [
        'company_id' => 'integer',
        'net_sales' => 'decimal:2',
        'beverage_tax' => 'decimal:2',
        'food_tax' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'partial_void' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'tips' => 'decimal:2',
        'mileage' => 'decimal:2',
        'total_tips_mileage' => 'decimal:2',
        'dd_tips' => 'decimal:2',
        'e_tips' => 'decimal:2',
        'e_tips_payroll' => 'decimal:2',
        'total_e_and_dd_tips' => 'decimal:2',
        'cash_payment' => 'decimal:2',
        'other_payments_total' => 'decimal:2',
        'total_cash_payment' => 'decimal:2',
        'net_cash_due' => 'decimal:2',
        'cash_bag' => 'decimal:2',
        'short_over' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'bank_child_amount_id' => 'integer',
        'settled' => 'boolean',
    ];

    protected $sortable = [
        'date',
        'company_id',
        'total_sales',
        'net_cash_due',
        'cash_bag',
        'short_over',
        'created_at',
        'bank_child_amount_id',
        'settled',
    ];

    protected $searchable = [
        'date',
        'company_id',
        'total_sales',
        'net_cash_due',
        'cash_bag',
        'short_over',
        'settled',
    ];

    protected $allowedFilters = [
        'date',
        'company_id',
        'total_sales',
        'net_cash_due',
        'cash_bag',
        'short_over',
        'created_at',
        'updated_at',
        'settled',
        'partial_void',
    ];

    public function otherPayments()
    {
        return $this->hasMany(DailySaleOtherPayment::class, 'daily_sale_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    public function bankDeposits()
    {
        return $this->hasMany(BankDeposit::class, 'daily_sale_id')->select('id', 'daily_sale_id', 'date', 'amount', 'notes');
    }

    public function shortages()
    {
        return $this->hasMany(Shortage::class, 'daily_sale_id')->select('id', 'daily_sale_id', 'date', 'amount', 'notes');
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }

    public function bankChildAmount()
    {
        return $this->belongsTo(BankEntryChildAmount::class, 'bank_child_amount_id');
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
