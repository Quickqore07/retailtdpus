<?php

namespace App\Models\AR;

use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\Settings\Ledger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PjPaymentItem extends Model
{
    use HasFactory;

    protected $table = 'pj_payments_items';

    protected $fillable = [
        'pj_payment_id',
        'ledger_id',
        'name',
        'amount',
        'settled',
        'bank_child_amount_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settled' => 'boolean',
    ];

    public function payment()
    {
        return $this->belongsTo(PjPayment::class, 'pj_payment_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id')->with('ledgerDetails')->select('id', 'name');
    }

    public function bankChildAmount()
    {
        return $this->belongsTo(BankEntryChildAmount::class, 'bank_child_amount_id')->select('id', 'deposit', 'fees', 'total_amount','settled','manual_settlement');
    }
}
