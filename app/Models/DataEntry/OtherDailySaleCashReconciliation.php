<?php

namespace App\Models\DataEntry;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherDailySaleCashReconciliation extends Model
{
    use HasFactory;

    protected $table = 'other_daily_sales_cash_recs';

    protected $fillable = [
        'other_daily_sale_id',
        'cash_payment',
        'detail',
        'amount',
        'is_default',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function otherDailySale()
    {
        return $this->belongsTo(OtherDailySale::class, 'other_daily_sale_id');
    }
}
