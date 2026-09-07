<?php

namespace App\Models;

use App\Models\AR\PjPaymentItem;
use App\Models\DataEntry\DailySale;
use App\Models\Settings\Ledger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;

class LedgerVouchers extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'ledger_vouchers';

    protected $fillable = [
        'ledger_id',
        'company_id',
        'opp_ledger_id',
        'amount',
        'debit',
        'credit',
        'voucher_id',
        'voucher_items_id',
        'voucher_type',
        'dbtable',
        'check_number',
        'description',
        'date',
    ];

    protected $activityFields = [
        ['field' => 'ledger_id', 'label' => 'Ledger', 'table' => 'ledgers', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'company_id', 'label' => 'Store', 'table' => 'company', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'opp_ledger_id', 'label' => 'Opp Ledger Id', 'type' => 'string'],
        ['field' => 'amount', 'label' => 'Amount', 'type' => 'number'],
        ['field' => 'debit', 'label' => 'Debit', 'type' => 'string'],
        ['field' => 'credit', 'label' => 'Credit', 'type' => 'string'],
        ['field' => 'voucher_id', 'label' => 'Voucher Id', 'type' => 'string'],
        ['field' => 'voucher_items_id', 'label' => 'Voucher Items Id', 'type' => 'string'],
        ['field' => 'voucher_type', 'label' => 'Voucher Type', 'type' => 'string'],
        ['field' => 'dbtable', 'label' => 'Dbtable', 'type' => 'string'],
        ['field' => 'check_number', 'label' => 'Check Number', 'type' => 'string'],
        ['field' => 'description', 'label' => 'Description', 'type' => 'string'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
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
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];
    protected $sortable = [
        'ledger_id',
        'opp_ledger_id',
        'amount',
        'debit',
        'credit',
    ];
    protected $searchable = [
        'ledger_id',
        'opp_ledger_id',
        'amount',
        'debit',
        'credit',
    ];
    protected $allowedFilters = [
        'ledger_id',
        'opp_ledger_id',
        'amount',
        'debit',
        'credit',
    ];
    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }
    public function opp_ledger()
    {
        return $this->belongsTo(Ledger::class, 'opp_ledger_id');
    }

    public function paymentItem()
    {
        return $this->belongsTo(PjPaymentItem::class, 'voucher_items_id')->where('voucher_type', 'pj_payments')->select('id', 'ledger_id', 'name', 'amount','settled');
    }
    public function dailySaleItem()
    {
        return $this->belongsTo(DailySale::class, 'voucher_items_id')->where('voucher_type', 'daily_sales')->select('id', 'ledger_id', 'name', 'cash_bag','settled');
    }
}