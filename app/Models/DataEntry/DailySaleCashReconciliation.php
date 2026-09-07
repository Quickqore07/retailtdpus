<?php

namespace App\Models\DataEntry;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySaleCashReconciliation extends Model
{
    use HasFactory;

    protected $table = 'daily_sales_cash_recs';

    protected $fillable = [
        'daily_sales_id',
        'cash_payment',
        'detail',
        'amount',
        'is_default',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function dailySale()
    {
        return $this->belongsTo(DailySale::class, 'daily_sales_id');
    }
}
