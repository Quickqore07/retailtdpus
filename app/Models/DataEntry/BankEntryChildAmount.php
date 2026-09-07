<?php

namespace App\Models\DataEntry;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\AuthorizedCompanies;
use App\Models\Traits\LogsActivity;
use App\Support\Filterable;
class BankEntryChildAmount extends Model
{
    use HasFactory, LogsActivity, Filterable, AuthorizedCompanies;

    protected $table = 'bank_entry_child_amounts';

    protected $fillable = [
        'bank_entry_item_id',           
        'deposit',
        'fees',
        'total_amount',
        'source',
        'source_id',
        'settled',
        'manual_settlement',
    ];

    protected $activityFields = [
        ['field' => 'bank_entry_item_id', 'label' => 'Bank Entry Item Id', 'type' => 'string'],
        ['field' => 'deposit', 'label' => 'Deposit', 'type' => 'string'],
        ['field' => 'fees', 'label' => 'Fees', 'type' => 'string'],
        ['field' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'source_id', 'label' => 'Source Id', 'type' => 'string'],
        ['field' => 'settled', 'label' => 'Settled', 'type' => 'string'],
        ['field' => 'manual_settlement', 'label' => 'Manual Settlement', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];



    protected $casts = [
        'deposit' => 'decimal:2',
        'fees' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'settled' => 'boolean',
        'manual_settlement' => 'boolean',
    ];

    public function bankEntryItem()
    {
        return $this->belongsTo(BankEntryItem::class, 'bank_entry_item_id');
    }
}