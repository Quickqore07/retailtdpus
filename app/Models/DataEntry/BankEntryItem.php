<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Ledger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankEntryItem extends Model
{
    use HasFactory;

    protected $table = 'bank_entry_items';

    protected $fillable = [
        'bank_entry_id',
        'ledger_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function entry()
    {
        return $this->belongsTo(BankEntry::class, 'bank_entry_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id')->with('ledgerDetails')->select('id', 'name');
    }

    public function bankEntryChildAmounts()
    {
        return $this->hasMany(BankEntryChildAmount::class, 'bank_entry_item_id');
    }
}
